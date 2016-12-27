<?php namespace WebEd\Base\ACL\Http\Controllers;

use WebEd\Base\ACL\Http\DataTables\RolesListDataTable;
use WebEd\Base\Core\Http\Controllers\BaseAdminController;
use WebEd\Base\ACL\Repositories\Contracts\RoleContract;
use WebEd\Base\ACL\Repositories\Contracts\PermissionContract;
use WebEd\Base\Core\Support\DataTable\DataTables;
use Yajra\Datatables\Engines\BaseEngine;

class RoleController extends BaseAdminController
{
    protected $module = 'webed-acl';

    /**
     * @var \WebEd\Base\ACL\Repositories\RoleRepository
     */
    protected $repository;

    public function __construct(RoleContract $roleRepository)
    {
        parent::__construct();

        $this->repository = $roleRepository;

        $this->getDashboardMenu($this->module . '-roles');

        $this->breadcrumbs
            ->addLink('ACL')
            ->addLink('Roles', route('admin::acl-roles.index.get'));
    }

    /**
     * Get index page
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(RolesListDataTable $rolesListDataTable)
    {
        $this->setPageTitle('Roles', 'All available roles');

        $this->dis['dataTable'] = $rolesListDataTable->run();

        return do_filter('acl-roles.index.get', $this)->viewAdmin('index-roles');
    }

    /**
     * Get all roles
     * @param RolesListDataTable|BaseEngine $rolesListDataTable
     * @return \Illuminate\Http\JsonResponse
     */
    public function postListing(RolesListDataTable $rolesListDataTable)
    {
        $data = $rolesListDataTable->with($this->groupAction());

        return do_filter('datatables.acl-roles.index.post', $data, $this);
    }

    /**
     * Handle group actions
     * @return array
     */
    private function groupAction()
    {
        $data = [];
        if ($this->request->get('customActionType', null) == 'group_action') {

            if(!$this->userRepository->hasPermission($this->loggedInUser, 'delete-roles')) {
                return [
                    'customActionMessage' => 'You do not have permission',
                    'customActionStatus' => 'danger',
                ];
            }

            $ids = (array)$this->request->get('id', []);

            $result = $this->repository->deleteRole($ids);

            $data['customActionMessage'] = $result['messages'];
            $data['customActionStatus'] = $result['error'] ? 'danger' : 'success';

        }
        return $data;
    }

    /**
     * Delete role
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDelete($id)
    {
        $result = $this->repository->deleteRole($id);

        do_action('acl-roles.after-delete.delete', $id, $result);

        return response()->json($result, $result['response_code']);
    }

    /**
     * @param \WebEd\Base\ACL\Repositories\PermissionRepository $permissionRepository
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getCreate(PermissionContract $permissionRepository)
    {
        $this->dis['superAdminRole'] = false;

        $this->setPageTitle('Create role');
        $this->breadcrumbs->addLink('Create role');

        $this->dis['checkedPermissions'] = [];

        $this->dis['permissions'] = $permissionRepository->orderBy('module', 'ASC')->get();

        $this->dis['currentId'] = 0;

        $this->dis['object'] = $this->repository->getModel();
        $oldInputs = old();
        if ($oldInputs) {
            foreach ($oldInputs as $key => $row) {
                $this->dis['object']->$key = $row;
            }
        }

        return do_filter('acl-roles.create.get', $this)->viewAdmin('edit-role');
    }

    /**
     * @param \WebEd\Base\ACL\Repositories\PermissionRepository $permissionRepository
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getEdit(PermissionContract $permissionRepository, $id)
    {
        $this->dis['superAdminRole'] = false;

        $item = $this->repository->find($id);

        if (!$item) {
            $this->flashMessagesHelper
                ->addMessages('Role not exists', 'danger')
                ->showMessagesOnSession();

            return redirect()->to(route('admin::acl-roles.index.get'));
        }

        $this->setPageTitle('Edit role', '#' . $id . ' ' . $item->name);
        $this->breadcrumbs->addLink('Edit role');

        $this->dis['object'] = $item;

        $this->dis['checkedPermissions'] = $this->repository->getRelatedPermissions($item);

        if ($item->slug == 'super-admin') {
            $this->dis['superAdminRole'] = true;
        }

        $this->dis['permissions'] = $permissionRepository->orderBy('module', 'ASC')->get();

        $this->dis['currentId'] = $id;

        return do_filter('acl-roles.edit.get', $this, $id)->viewAdmin('edit-role');
    }

    public function postEdit($id)
    {
        if (!$id) {
            $result = $this->createRole();
        } else {
            $result = $this->updateRole($id);
        }

        $msgType = $result['error'] ? 'danger' : 'success';

        $this->flashMessagesHelper
            ->addMessages($result['messages'], $msgType)
            ->showMessagesOnSession();

        if ($result['error']) {
            if(!$id) {
                return redirect()->back()->withInput();
            }
            return redirect()->back();
        }

        do_action('acl-roles.after-edit.post', $id, $result, $this);

        if ($this->request->has('_continue_edit')) {
            if (!$id) {
                return redirect()->to(route('admin::acl-roles.edit.get', ['id' => $result['data']->id]));
            }
            return redirect()->back();
        }

        return redirect()->to(route('admin::acl-roles.index.get'));
    }

    protected function createRole()
    {
        if(!$this->userRepository->hasPermission($this->loggedInUser, 'create-roles')) {
            return redirect()->to(route('admin::error', ['code' => 403]));
        }

        return $this->repository->createRole(array_merge($this->request->except(['_token', '_continue_edit']), [
            'permissions' => ($this->request->exists('permissions') ? $this->request->get('permissions') : [])
        ]));
    }

    protected function updateRole($id)
    {
        return $this->repository->updateRole($id, array_merge($this->request->except(['_token', 'slug', '_continue_edit']), [
            'permissions' => ($this->request->exists('permissions') ? $this->request->get('permissions') : [])
        ]));
    }
}
