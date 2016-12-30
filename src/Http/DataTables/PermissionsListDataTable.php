<?php namespace WebEd\Base\ACL\Http\DataTables;

use WebEd\Base\ACL\Repositories\Contracts\PermissionContract;
use WebEd\Base\Core\Http\DataTables\AbstractDataTables;

class PermissionsListDataTable extends AbstractDataTables
{
    protected $repository;

    public function __construct(PermissionContract $repository)
    {
        $this->repository = $repository;

        $this->repository->select('name', 'slug', 'module', 'id');

        parent::__construct();
    }

    /**
     * @return string
     */
    public function run()
    {
        $this->setAjaxUrl(route('admin::acl-permissions.index.post'), 'POST');

        $this
            ->addHeading('id', 'ID', '1%')
            ->addHeading('name', 'Name', '35%')
            ->addHeading('alias', 'Alias', '30%')
            ->addHeading('module', 'Module', '35%')
        ;

        $this
            ->addFilter(1, form()->text('name', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => 'Search...'
            ]))
            ->addFilter(2, form()->text('module', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => 'Search...'
            ]))
            ->addFilter(3, form()->text('slug', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => 'Search...'
            ]));

        $this->setColumns([
            ['data' => 'id', 'name' => 'id'],
            ['data' => 'name', 'name' => 'name'],
            ['data' => 'slug', 'name' => 'slug'],
            ['data' => 'module', 'name' => 'module'],
        ]);

        return $this->view();
    }

    /**
     * @return $this
     */
    protected function fetch()
    {
        $this->fetch = datatable()->of($this->repository);

        return $this;
    }
}
