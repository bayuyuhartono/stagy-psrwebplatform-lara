<?php

namespace App\Rules;

use App\Features\ContentBlocks\ContentBlockRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidContentBlocks implements ValidationRule
{
    public function __construct(private ContentBlockRegistry $registry) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $index => $block) {
            if (! is_array($block)) {
                $fail("Block {$index} must be an object.");

                continue;
            }

            $baseValidator = Validator::make($block, [
                '' => ['array:id,type,data'],
                'id' => ['nullable', 'uuid'],
                'type' => ['required', 'string'],
                'data' => ['required', 'array'],
            ]);

            if ($baseValidator->fails()) {
                $fail("Block {$index}: {$baseValidator->errors()->first()}");

                continue;
            }

            $type = $block['type'];

            if (! $this->registry->supports($type)) {
                $fail("Block {$index} uses unsupported type [{$type}].");

                continue;
            }

            $dataValidator = Validator::make($block, $this->registry->rulesFor($type));

            if ($dataValidator->fails()) {
                $fail("Block {$index} ({$type}): {$dataValidator->errors()->first()}");
            }
        }
    }
}
