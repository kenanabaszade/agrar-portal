<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $faqId = $this->route('faq') ? $this->route('faq')->id : null;
        
        return [
            'question' => [
                'required',
                'string',
                'max:1000'
            ],
            'answer' => [
                'required',
                'string',
                'max:2000'
            ],
            'category' => [
                'required',
                'string',
                'max:255'
            ],
            'is_active' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Sual mütləqdir.',
            'question.max' => 'Sual ən çox 1000 simvol ola bilər.',
            'answer.required' => 'Cavab mütləqdir.',
            'answer.max' => 'Cavab ən çox 2000 simvol ola bilər.',
            'category.required' => 'Kateqoriya mütləqdir.',
            'category.max' => 'Kateqoriya ən çox 255 simvol ola bilər.',
            'is_active.boolean' => 'Aktiv status boolean olmalıdır.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'question' => 'sual',
            'answer' => 'cavab',
            'category' => 'kateqoriya',
            'is_active' => 'aktiv status',
        ];
    }
}
