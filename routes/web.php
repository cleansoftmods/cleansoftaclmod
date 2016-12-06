<?php use Illuminate\Routing\Router;

/**
 *
 * @var Router $router
 *
 */

$router->group(['middleware' => 'web'], function (Router $router) {

    $adminRoute = config('webed.admin_route');

    $moduleRoute = 'acl';

    /**
     * Admin routes
     */
    $router->group(['prefix' => $adminRoute . '/' . $moduleRoute], function (Router $router) use ($adminRoute, $moduleRoute) {
        $router->get('', function () {
            return redirect()->to(route('admin::acl-roles.index.get'));
        });
        /**
         * Roles
         */
        $router->get('/roles', 'RoleController@getIndex')
            ->name('admin::acl-roles.index.get')
            ->middleware('has-permission:view-roles');

        $router->post('/roles', 'RoleController@postListing')
            ->name('admin::acl-roles.index.get-json')
            ->middleware('has-permission:view-roles');

        $router->get('/roles/create', 'RoleController@getCreate')
            ->name('admin::acl-roles.create.get')
            ->middleware('has-permission:create-roles');

        $router->get('/roles/edit/{id}', 'RoleController@getEdit')
            ->name('admin::acl-roles.edit.get')
            ->middleware('has-permission:edit-roles');

        $router->post('/roles/edit/{id}', 'RoleController@postEdit')
            ->name('admin::acl-roles.edit.post')
            ->middleware('has-permission:edit-roles');

        $router->delete('/roles/{id}', 'RoleController@deleteDelete')
            ->name('admin::acl-roles.delete.delete')
            ->middleware('has-permission:delete-roles');

        /**
         * Permissions
         */
        $router->get('/permissions', 'PermissionController@getIndex')
            ->name('admin::acl-permissions.index.get')
            ->middleware('has-permission:view-permissions');

        $router->post('/permissions', 'PermissionController@postListing')
            ->name('admin::acl-permissions.index.post')
            ->middleware('has-permission:view-permissions');
    });
});
