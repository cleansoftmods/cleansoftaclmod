<?php namespace WebEd\Base\ACL\Http\Requests;

use WebEd\Base\Core\Http\Requests\Request;

class UpdateRoleRequest extends Request
{
    public $rules = [
        'name' => 'required|max:255|string',
    ];
}
