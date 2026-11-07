<?php

namespace App\Rules;

use App\Models\Menu;
use App\Models\MenuItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidMenuParent implements ValidationRule
{
    public function __construct(private Menu $menu, private ?MenuItem $currentItem = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parent = MenuItem::query()->find($value);

        if ($parent === null || $parent->menu_id !== $this->menu->getKey()) {
            $fail('The selected :attribute must belong to this menu.');

            return;
        }

        while ($parent !== null) {
            if ($this->currentItem !== null && $parent->is($this->currentItem)) {
                $fail('The selected :attribute would create a circular menu hierarchy.');

                return;
            }

            $parent = $parent->parent;
        }
    }
}
