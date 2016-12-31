<?php namespace WebEd\Base\ACL\Http\Controllers;

use WebEd\Base\ACL\Http\DataTables\PermissionsListDataTable;
use WebEd\Base\Core\Http\Controllers\BaseAdminController;
use WebEd\Base\ACL\Repositories\Contracts\PermissionRepositoryContract;

class PermissionController extends BaseAdminController
{
    protected $module = 'webed-acl';

    /**
     * @var \WebEd\Base\ACL\Repositories\PermissionRepository
     */
    protected $repository;

    public function __construct(PermissionRepositoryContract $repository)
    {
        parent::__construct();

        $this->repository = $repository;

        $this->getDashboardMenu($this->module . '-permissions');

        $this->breadcrumbs->addLink('ACL')->addLink('Permissions', route('admin::acl-permissions.index.get'));;
    }

    public function getIndex(PermissionsListDataTable $permissionsListDataTable)
    {
        $this->setPageTitle('Permissions', 'All available permissions');

        $this->dis['dataTable'] = $permissionsListDataTable->run();

        return do_filter('acl-permissions.index.get', $this)->viewAdmin('permissions.index');
    }

    public function postListing(PermissionsListDataTable $permissionsListDataTable)
    {
        return do_filter('datatables.acl-permissions.index.post', $permissionsListDataTable, $this);
    }
}
