<?php namespace Pyro\PermissionTreeModule;

use Anomaly\Streams\Platform\Addon\AddonServiceProvider;
use Anomaly\Streams\Platform\Ui\Form\FormBuilder;
use Anomaly\UsersModule\Role\Permission\PermissionFormBuilder as RolePermissionFormBuilder;
use Anomaly\UsersModule\User\Permission\PermissionFormBuilder as UserPermissionFormBuilder;
use Illuminate\Routing\Router;
use Pyro\PermissionTreeModule\Command\ModifyPermissionForm;

class PermissionTreeModuleServiceProvider extends AddonServiceProvider
{


    public function boot()
    {
        $this->app->extend(RolePermissionFormBuilder::class, function(RolePermissionFormBuilder $builder){
            $builder->listen('built', function ($builder) {
                dispatch_now(new ModifyPermissionForm($builder));
            });
            return $builder;
        });
        $this->app->extend(UserPermissionFormBuilder::class, function(UserPermissionFormBuilder $builder){
            $builder->listen('built', function ($builder) {
                dispatch_now(new ModifyPermissionForm($builder));
            });
            return $builder;
        });
    }

}
