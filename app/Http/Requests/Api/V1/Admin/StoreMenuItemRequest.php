<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\Menus\Enums\MenuItemTarget;
use App\Features\Menus\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\Page;
use App\Rules\SafeMenuUrl;
use App\Rules\ValidMenuParent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('menu'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Menu $menu */
        $menu = $this->route('menu');

        return $this->itemRules($menu);
    }

    /** @return array<string, mixed> */
    protected function itemRules(Menu $menu): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(MenuItemType::class)],
            'page_id' => ['nullable', 'uuid', Rule::requiredIf($this->input('type') === MenuItemType::Page->value), Rule::exists(Page::class, 'id')],
            'url' => ['nullable', 'string', 'max:2048', Rule::requiredIf($this->input('type') === MenuItemType::Custom->value), new SafeMenuUrl],
            'target' => ['sometimes', Rule::enum(MenuItemTarget::class)],
            'parent_id' => ['nullable', 'uuid', new ValidMenuParent($menu)],
            'position' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
