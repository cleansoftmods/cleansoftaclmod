<?php namespace WebEd\Base\ACL\Repositories;

use WebEd\Base\Caching\Services\Traits\Cacheable;
use WebEd\Base\Repositories\Eloquent\EloquentBaseRepository;

use WebEd\Base\ACL\Repositories\Contracts\PermissionRepositoryContract;
use WebEd\Base\Caching\Services\Contracts\CacheableContract;

class PermissionRepository extends EloquentBaseRepository implements PermissionRepositoryContract, CacheableContract
{
    use Cacheable;

    protected $rules = [
        'name' => 'required|between:3,100|string',
        'slug' => 'required|between:3,100|unique:roles|alpha_dash',
        'module' => 'required|max:255',
    ];

    protected $editableFields = [
        'name',
        'slug',
        'module',
    ];

    public function get(array $columns = ['*'])
    {
        $this->model = $this->model->orderBy('module', 'ASC');
        return parent::get($columns);
    }

    /**
     * Register permission
     * @param $name
     * @param $alias
     * @param $module
     * @param bool $force
     * @return array|\WebEd\Base\ACL\Repositories\PermissionRepository
     */
    public function registerPermission($name, $alias, $module, $force = true)
    {
        $permission = $this->model->where(['slug' => $alias])->first();
        if (!$permission) {
            $result = $this->editWithValidate(0, [
                'name' => $name,
                'slug' => str_slug($alias),
                'module' => $module,
            ], true, false);
            if (!$result['error']) {
                if (!$force) {
                    return response_with_messages($result['messages'], false, \Constants::SUCCESS_NO_CONTENT_CODE);
                }
            }
            if (!$force) {
                return response_with_messages($result['messages'], true, \Constants::ERROR_CODE);
            }
        }
        if (!$force) {
            return response_with_messages('Permission alias exists', true, \Constants::ERROR_CODE);
        }
        return $this;
    }

    /**
     * @param string|array $alias
     * @param bool $force
     * @return array|\WebEd\Base\ACL\Repositories\PermissionRepository
     */
    public function unsetPermission($alias, $force = true)
    {
        $result = $this->model->whereIn('slug', (array)$alias)->delete();
        if (!$result['error']) {
            if (!$force) {
                return response_with_messages($result['messages'], false, \Constants::SUCCESS_NO_CONTENT_CODE);
            }
        }
        if (!$force) {
            return response_with_messages($result['messages'], true, \Constants::ERROR_CODE);
        }
        return $this;
    }

    /**
     * @param string|array $module
     * @param bool $force
     * @return array|\WebEd\Base\ACL\Repositories\PermissionRepository
     */
    public function unsetPermissionByModule($module, $force = true)
    {
        $result = $this->model->whereIn('module', (array)$module)->delete();
        if (!$result['error']) {
            if (!$force) {
                return response_with_messages($result['messages'], false, \Constants::SUCCESS_NO_CONTENT_CODE);
            }
        }
        if (!$force) {
            return response_with_messages($result['messages'], true, \Constants::ERROR_CODE);
        }
        return $this;
    }
}
