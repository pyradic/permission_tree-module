<?php

namespace Pyro\PermissionTreeModule\Command;

use Anomaly\Streams\Platform\Addon\AddonCollection;
use Anomaly\Streams\Platform\Asset\Asset;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ModifyPermissionForm
{
    /** @var \Anomaly\Streams\Platform\Ui\Form\FormBuilder */
    protected $builder;

    public function __construct($builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Anomaly\Streams\Platform\Asset\Asset     $assets
     *
     * @return void
     * @throws \Exception
     */
    public function handle(Container $container, Asset $assets, AddonCollection $addons)
    {
        $this->builder->setFormOption('class', 'pyro-permission-tree__permission-form');
        $this->builder->setFormOption('form_view', 'pyro.module.permission_tree::permission-form');

        $data = collect($this->builder->getSections())
            ->filter(function ($section, $key) use ($addons) {
                if ($addons->has($key)) {
                    /** @var \Anomaly\Streams\Platform\Addon\Module\Module|\Anomaly\Streams\Platform\Addon\Extension\Extension $addon */
                    $addon = $addons->get($key);
                    if ($addon->isInstalled() === false) {
                        return false;
                    }
                }
                return true;
            })
            ->map(function ($section, $key) {
                $addon[ 'key' ] = $key;
                $addon[ 'label' ]   = trans($section[ 'title' ]);
                $addon[ 'streams' ] = collect($section[ 'fields' ])->map(function ($fieldSlug) {
                    $field       = $this->builder->getFormField($fieldSlug);
                    $value       = Arr::wrap($field->getValue());
                    $fieldKey    = str_replace('_', '.', $fieldSlug);
                    $permissions = [];
                    foreach ($field->config('options') as $key => $label) {
                        $label   = trans($label);
                        $enabled = in_array($key, $value, true);

                        $permission    = Str::replaceFirst($fieldKey . '.', '', $key);
                        $permissions[] = compact('label', 'enabled', 'key', 'permission');
                    }

                    return [ 'permissions' => $permissions, 'label' => $field->getLabel(), 'field' => $fieldSlug, 'key' => $fieldKey ];
                })->toArray();
                return $addon;
            })
            ->values()
            ->toArray();


        app()->platform
            ->addPublicScript('assets/js/pyro__permission_tree.js')
            ->addPublicStyle('assets/css/pyro__permission_tree.css')
            ->addProvider('pyro.pyro__permission_tree.PermissionTreeServiceProvider')
            ->set('permission_tree.permissions', $data);

//        $assets->add('scripts.js', 'pyro.module.permission_tree::js/addon.js', [ 'webpack:permission-tree:scripts' ]);
//        app()->platform->addProvider('pyro.pyro__permission_tree.PermissionTreeServiceProvider');
//        app()->platform->getData()->set('ex2.permissions', $data);
//        $this->builder->addFormData('data', $data);
    }

}
