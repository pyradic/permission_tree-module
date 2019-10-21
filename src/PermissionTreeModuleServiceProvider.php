<?php namespace Pyro\PermissionTreeModule;

use Anomaly\Streams\Platform\Addon\AddonServiceProvider;
use Anomaly\Streams\Platform\Ui\Form\FormBuilder;
use Anomaly\UsersModule\Role\Permission\PermissionFormBuilder as RolePermissionFormBuilder;
use Anomaly\UsersModule\User\Permission\PermissionFormBuilder as UserPermissionFormBuilder;
use Illuminate\Routing\Router;
use Pyro\PermissionTreeModule\Command\ModifyPermissionForm;

class PermissionTreeModuleServiceProvider extends AddonServiceProvider
{


    public function boot(RolePermissionFormBuilder $roleBuilder, UserPermissionFormBuilder $userBuilder)
    {
        $this->modifyForm($roleBuilder);
        $this->modifyForm($userBuilder);
    }

    protected function modifyForm(FormBuilder $builder)
    {
        $builder->listen('built', function ($builder) {
            $this->dispatchNow(new ModifyPermissionForm($builder));
        });
    }
}
