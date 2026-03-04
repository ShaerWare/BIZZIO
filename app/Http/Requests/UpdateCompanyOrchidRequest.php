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

            // Orchid Upload отправляет ID вложений (integer) для существующих файлов
            // и UploadedFile для новых. Валидация файлов на клиенте (acceptedFiles).
            'company.logo'              => ['sometimes', 'nullable'],
            'company.documents'         => ['sometimes', 'nullable'],

            'company.is_verified'       => ['nullable', 'boolean'],

            'company.moderators'        => ['nullable', 'array'],
            'company.moderators.*'      => ['exists:users,id'],
        ];
    }
}