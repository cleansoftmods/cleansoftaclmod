<?php namespace WebEd\Base\ACL\Http\DataTables;

use WebEd\Base\ACL\Repositories\Contracts\RoleContract;
use WebEd\Base\ACL\Repositories\RoleRepository;
use WebEd\Base\Core\Http\DataTables\AbstractDataTables;

class RolesListDataTable extends AbstractDataTables
{
    /**
     * @var RoleRepository
     */
    protected $repository;

    public function __construct(RoleContract $repository)
    {
        $this->repository = $repository;

        $this->repository->select('id', 'name', 'slug');

        parent::__construct();
    }

    /**
     * @return string
     */
    public function run()
    {
        $this->setAjaxUrl(route('admin::acl-roles.index.get-json'), 'POST');

        $this
            ->addHeading('name', 'Name', '50%')
            ->addHeading('alias', 'Alias', '30%')
            ->addHeading('actions', 'Actions', '20%');

        $this
            ->addFilter(1, form()->text('name', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => 'Search...'
            ]))
            ->addFilter(2, form()->text('slug', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => 'Search...'
            ]));

        $this->withGroupActions([
            '' => 'Select' . '...',
            'deleted' => 'Deleted',
        ]);

        $this->setColumns([
            ['data' => 'id', 'name' => 'id', 'searchable' => false, 'orderable' => false],
            ['data' => 'name', 'name' => 'name'],
            ['data' => 'slug', 'name' => 'slug'],
            ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
        ]);

        return $this->view();
    }

    /**
     * @return $this
     */
    protected function fetch()
    {
        $this->fetch = datatable()->of($this->repository)
            ->editColumn('id', function ($item) {
                return form()->customCheckbox([
                    ['id[]', $item->id]
                ]);
            })
            ->addColumn('actions', function ($item) {
                /*Edit link*/
                $deleteLink = route('admin::acl-roles.delete.delete', ['id' => $item->id]);
                $editLink = route('admin::acl-roles.edit.get', ['id' => $item->id]);

                /*Buttons*/
                $editBtn = link_to($editLink, 'Edit', ['class' => 'btn btn-outline green btn-sm']);
                $deleteBtn = ($item->status != 'deleted') ? form()->button('Delete', [
                    'title' => 'Delete this item',
                    'data-ajax' => $deleteLink,
                    'data-method' => 'DELETE',
                    'data-toggle' => 'confirmation',
                    'class' => 'btn btn-outline red-sunglo btn-sm ajax-link',
                ]) : '';

                $deleteBtn = ($item->status != 'deleted') ? $deleteBtn : '';

                return $editBtn . $deleteBtn;
            });

        return $this;
    }
}
