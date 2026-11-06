<?php

declare(strict_types=1);

namespace App\Http\Requests\Internal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemGovernanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->role === 'superadmin' ||
            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Batch intervals payload
            'intervals' => ['sometimes', 'required', 'array', 'min:1'],
            'intervals.*.id' => ['nullable', 'integer'],
            'intervals.*.kategori' => ['required_with:intervals', 'string', 'max:50'],
            'intervals.*.tier' => ['required_with:intervals', 'integer', 'between:1,10'],
            'intervals.*.label' => ['required_with:intervals', 'string', 'max:100'],
            'intervals.*.min_value' => ['required_with:intervals', 'numeric', 'min:0'],
            'intervals.*.max_value' => ['nullable', 'numeric'],
            'intervals.*.color_hex' => ['required_with:intervals', 'string', 'max:20'],
            'intervals.*.satuan' => ['nullable', 'string', 'max:50'],
            'intervals.*.calculation_mode' => ['nullable', 'string', 'in:manual,auto,quantile,jenks'],
            'intervals.*.is_active' => ['nullable', 'boolean'],
            'intervals.*.effective_date' => ['nullable', 'date'],

            // Single interval update payload
            'kategori' => ['sometimes', 'required', 'string', 'max:50'],
            'tier' => ['sometimes', 'required', 'integer', 'between:1,10'],
            'label' => ['sometimes', 'required', 'string', 'max:100'],
            'min_value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'max_value' => ['nullable', 'numeric'],
            'color_hex' => ['sometimes', 'required', 'string', 'max:20'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'calculation_mode' => ['nullable', 'string', 'in:manual,auto,quantile,jenks'],
            'is_active' => ['nullable', 'boolean'],
            'effective_date' => ['nullable', 'date'],
        ];
    }
}
