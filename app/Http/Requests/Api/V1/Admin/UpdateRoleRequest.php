<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('role'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique(Role::class)->ignore($this->route('role'))],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'permission_ids' => ['sometimes', 'present', 'array'],
            'permission_ids.*' => ['uuid', Rule::exists(Permission::class, 'id')],
        ];
    }
}
