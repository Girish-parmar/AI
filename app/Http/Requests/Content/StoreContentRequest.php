<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for creating a course or a script — the two content
 * types have identical fields.
 */
class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
