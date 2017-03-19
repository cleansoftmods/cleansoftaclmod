<?php namespace WebEd\Base\ACL\Http\Controllers;

use WebEd\Base\ACL\Http\DataTables\RolesListDataTable;
use WebEd\Base\ACL\Http\Requests\CreateRoleRequest;
use WebEd\Base\ACL\Http\Requests\UpdateRoleRequest;
use WebEd\Base\Http\Controllers\BaseAdminController;
use WebEd\Base\ACL\Repositories\Contracts\RoleRepositoryContract;
use WebEd\Base\ACL\Repositories\Contracts\PermissionRepositoryContract;
use WebEd\Base\Support\DataTable\DataTables;
use Yajra\Datatables\Engines\BaseEngine;

class RoleController extends BaseAdminController
{
    protected $module = 'webed-acl';

    /**
     * @var \WebEd\Base\ACL\Repositories\RoleRepository
     */
    protected $repository;

    public function __construct(RoleRepositoryContract $roleRepository)
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

        return do_filter('acl-roles.index.get', $this, $rolesListDataTable)->viewAdmin('roles.index');
    }

    /**
     * Get all roles
     * @param RolesListDataTable|BaseEngine $rolesListDataTable
     * @return \Illuminate\Http\JsonResponse
     */
    public function postListing(RolesListDataTable $rolesListDataTable)
    {
        $data = $rolesListDataTable->with($this->groupAction());

        return do_filter('datatables.acl-roles.index.post', $data, $this, $rolesListDataTable);
    }

    /**
     * Handle group actions
     * @return array
     */
    private function groupAction()
    {
        $data = [];
        if ($this->request->get('customActionType', null) == 'group_action') {

            if(!$this->userRepository->hasPermission($this->loggedInUser, ['delete-roles'])) {
                return [
                    'customActionMessage' => trans('webed-acl::base.do_not_have_permission'),
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
    public function getCreate(PermissionRepositoryContract $permissionRepository)
    {
        $this->dis['superAdminRole'] = false;

        $this->setPageTitle('Create role');
        $this->breadcrumbs->addLink('Create role');

        $this->dis['checkedPermissions'] = [];

        $this->dis['permissions'] = $permissionRepository->orderBy('module', 'ASC')->get();

        $this->dis['object'] = $this->repository->getModel();
        $oldInputs = old();
        if ($oldInputs) {
            foreach ($oldInputs as $key => $row) {
                if($key === 'permissions') {
                    $this->dis['checkedPermissions'] = $row;
                    continue;
                }
                $this->dis['object']->$key = $row;
            }
        }

        return do_filter('acl-roles.create.get', $this)->viewAdmin('roles.create');
    }

    public function postCreate(CreateRoleRequest $request)
    {
        $data = [
            'name' => $request->get('name'),
            'slug' => $request->get('slug'),
            'permissions' => ($request->exists('permissions') ? $request->get('permissions') : []),
            'created_by' => $this->loggedInUser->id,
            'updated_by' => $this->loggedInUser->id,
        ];
        $result = $this->repository->createRole($data);

        $msgType = $result['error'] ? 'danger' : 'success';

        flash_messages()
            ->addMessages($result['messages'], $msgType)
            ->showMessagesOnSession();

        if ($result['error']) {
            return redirect()->back()->withInput();
        }

        do_action('acl-roles.after-create.post', $result['data']->id, $result, $this);

        if ($this->request->has('_continue_edit')) {
            return redirect()->to(route('admin::acl-roles.edit.get', ['id' => $result['data']->id]));
        }

        return redirect()->to(route('admin::acl-roles.index.get'));
    }

    /**
     * @param \WebEd\Base\ACL\Repositories\PermissionRepository $permissionRepository
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getEdit(PermissionRepositoryContract $permissionRepository, $id)
    {
        $this->dis['superAdminRole'] = false;

        $item = $this->repository->find($id);

        if (!$item) {
            flash_messages()
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

        return do_filter('acl-roles.edit.get', $this, $id)->viewAdmin('roles.edit');
    }

    public function postEdit(UpdateRoleRequest $request, $id)
    {
        $item = $this->repository->find($id);

        if (!$item) {
            flash_messages()
                ->addMessages('Role not exists', 'danger')
                ->showMessagesOnSession();

            return redirect()->to(route('admin::acl-roles.index.get'));
        }

        $data = [
            'name' => $request->get('name'),
            'permissions' => ($request->exists('permissions') ? $request->get('permissions') : []),
            'updated_by' => $this->loggedInUser->id,
        ];

        $result = $this->repository->updateRole($item, $data);

        $msgType = $result['error'] ? 'danger' : 'success';

        flash_messages()
            ->addMessages($result['messages'], $msgType)
            ->showMessagesOnSession();

        if ($result['error']) {
            return redirect()->back();
        }

        do_action('acl-roles.after-edit.post', $id, $result, $this);

        if ($this->request->has('_continue_edit')) {
            return redirect()->back();
        }

        return redirect()->to(route('admin::acl-roles.index.get'));
    }
}
