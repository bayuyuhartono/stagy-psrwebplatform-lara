<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Role::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique(Role::class)],
            'label' => ['required', 'string', 'max:255'],
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['uuid', Rule::exists(Permission::class, 'id')],
        ];
    }
}
