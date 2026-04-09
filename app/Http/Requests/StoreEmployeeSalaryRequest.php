<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'salary_month_bs' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'salary_date_bs' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'leaves' => ['nullable', 'array'],
            'leaves.*' => ['nullable', 'numeric', 'min:0'],
            'overtimes' => ['nullable', 'array'],
            'overtimes.*' => ['nullable', 'numeric', 'min:0'],
            'save_as_expense' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
