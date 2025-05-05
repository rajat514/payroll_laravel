<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class Store extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|min:2|max:191',
            'last_name' => 'required|string|min:2|max:191',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'date_of_joining' => 'required|date',
            'date_of_retirement' => 'nullable|date',
            'gis_eligibility' => 'required|in:1,0',
            'pwd_status' => 'required|in:1,0',
            'pension_scheme' => 'required|in:GPF,NPS',
            'pension_number' => 'nullable|string|min:2|max:191',
            'gis_no' => 'nullable|string|min:2|max:191',
            'credit_society_member' => 'required|in:1,0',
            'email' => 'required|email|max:191|unique:employees,email',
            'increment_month' => 'nullable|string',
            'uniform_allowance_eligibility' => 'required|in:1,0',
            'hra_eligibility' => 'required|in:1,0',
            'npa_eligibility' => 'required|in:1,0',
            'pancard' => [
                'required',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                // 'unique:employees,pancard',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pancard.regex' => 'The PAN card format is invalid. It should be like ABCDE1234F.'
        ];
    }
}
