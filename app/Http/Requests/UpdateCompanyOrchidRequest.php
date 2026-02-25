<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyOrchidRequest extends FormRequest
{
    protected ?Company $companyModel = null;

    public function authorize(): bool
    {
        $company = $this->route('company');

        if (!$company) {
            return auth()->check(); // создание — разрешено
        }

        $this->companyModel = $company;

        return auth()->check() && (
            $this->companyModel->created_by === auth()->id() ||
            $this->companyModel->isModerator(auth()->user()) ||
            auth()->user()->hasAccess('platform.systems.roles')
        );
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'company.name'              => ['sometimes', 'required', 'string', 'max:255'],
            'company.inn'               => [
                'sometimes',
                'required',
                'string',
                'size:10',
                'regex:/^\d{10}$/',
                Rule::unique('companies', 'inn')->ignore($companyId)->whereNull('deleted_at'),
            ],
            'company.legal_form'        => ['nullable', 'string', 'max:255'],
            'company.short_description' => ['nullable', 'string', 'max:500'],
            'company.full_description'  => ['nullable', 'string'],
            'company.industry_id'       => ['nullable', 'exists:industries,id'],

            // Логотип — валидируем ТОЛЬКО если пришёл файл
            'company.logo'              => ['sometimes', 'nullable', 'array'],
            'company.logo.*'            => ['sometimes', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],

            'company.documents'         => ['sometimes', 'nullable', 'array'],
            'company.documents.*'       => ['sometimes', 'file', 'mimes:pdf', 'max:10240'],

            'company.is_verified'       => ['nullable', 'boolean'],

            'company.moderators'        => ['nullable', 'array'],
            'company.moderators.*'      => ['exists:users,id'],
        ];
    }
}