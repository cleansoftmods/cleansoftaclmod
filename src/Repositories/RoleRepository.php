<?php namespace WebEd\Base\ACL\Repositories;

use WebEd\Base\ACL\Models\Contracts\RoleModelContract;
use WebEd\Base\Core\Repositories\AbstractBaseRepository;

use WebEd\Base\ACL\Repositories\Contracts\RoleRepositoryContract;
use WebEd\Base\Caching\Services\Contracts\CacheableContract;

class RoleRepository extends AbstractBaseRepository implements RoleRepositoryContract, CacheableContract
{
    protected $rules = [
        'name' => 'required|between:3,100|string',
        'slug' => 'required|between:3,100|unique:roles|alpha_dash',
        'created_by' => 'required|min:0',
        'updated_by' => 'required|min:0',
    ];

    protected $editableFields = [
        'name',
        'slug',
        'created_by',
        'updated_by',
    ];

    /**
     * The roles with these alias cannot be deleted
     * @var array
     */
    protected $cannotDelete = ['super-admin'];

    /**
     * @param \WebEd\Base\ACL\Models\Role $model
     * @param \Illuminate\Database\Eloquent\Collection|array $data
     */
    public function syncPermissions($model, $data)
    {
        $model->permissions()->sync($data);
    }

    /**
     * @param array|int $id
     * @return array
     */
    public function deleteRole($id)
    {
        $result = $this->where('slug', 'NOT_IN', $this->cannotDelete)
            ->where('id', 'IN', (array)$id)
            ->delete();

        if (!$result['error']) {
            return $this->setMessages($result['messages'], false, \Constants::SUCCESS_NO_CONTENT_CODE);
        }
        return $this->setMessages($result['messages'], true, \Constants::ERROR_CODE);
    }

    /**
     * @param array $data
     * @return array
     */
    public function createRole($data)
    {
        $result = $this->editWithValidate(0, $data, true, false);

        if ($result['error']) {
            return $this->setMessages($result['messages'], true, \Constants::ERROR_CODE);
        }

        /**
         * Sync permissions
         */
        if (isset($data['permissions']) && $data['permissions']) {
            $this->syncPermissions($result['data'], $data['permissions']);
        }

        $result = $this->setMessages('Create role success', false, \Constants::SUCCESS_CODE, $result['data']);

        return $result;
    }

    /**
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateRole($id, $data)
    {
        $result = $this->editWithValidate($id, $data, false, true);

        if ($result['error']) {
            return $this->setMessages($result['messages'], true, \Constants::ERROR_CODE);
        }

        /**
         * Sync permissions
         */
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $this->syncPermissions($result['data'], $data['permissions']);
        }

        $result = $this->setMessages('Update role success', false, \Constants::SUCCESS_CODE, $result['data']);

        return $result;
    }

    /**
     * @param int|\WebEd\Base\ACL\Models\Contracts\RoleModelContract $id
     * @return array
     */
    public function getRelatedPermissions($id)
    {
        if ($id instanceof RoleModelContract) {
            $item = $id;
        } else {
            $item = $this->find($id);
        }

        if (!$item) {
            return [];
        }

        return $item->permissions()->allRelatedIds()->toArray();
    }
}
