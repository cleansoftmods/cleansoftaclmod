<?php namespace WebEd\Base\ACL\Providers;

use Illuminate\Support\ServiceProvider;

class BootstrapModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        app()->booted(function () {
            $this->booted();
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }

    protected function booted()
    {
        /**
         * Register to dashboard menu
         */
        \DashboardMenu::registerItem([
            'id' => 'webed-acl-roles',
            'priority' => 3.1,
            'parent_id' => null,
            'heading' => null,
            'title' => trans('webed-acl::base.roles'),
            'font_icon' => 'icon-lock',
            'link' => route('admin::acl-roles.index.get'),
            'css_class' => null,
            'permissions' => ['view-roles'],
        ])->registerItem([
            'id' => 'webed-acl-permissions',
            'priority' => 3.2,
            'parent_id' => null,
            'heading' => null,
            'title' => trans('webed-acl::base.permissions'),
            'font_icon' => 'icon-shield',
            'link' => route('admin::acl-permissions.index.get'),
            'css_class' => null,
            'permissions' => ['view-permissions'],
        ]);
    }
}
