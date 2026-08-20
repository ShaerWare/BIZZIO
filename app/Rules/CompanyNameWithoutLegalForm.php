<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * #287 Название компании хранится без организационно-правовой формы и кавычек: ОПФ выбирается
 * отдельным полем, а полное юридическое наименование собирается из них
 * (`Company::legalNameWithInn()` → ООО «Ромашка» (ИНН …)). Если ОПФ и кавычки попадут в само
 * название, в протоколах получится «ООО «ООО "Ромашка"»».
 */
class CompanyNameWithoutLegalForm implements ValidationRule
{
    /** Формы, которые пользователи чаще всего вписывают в название. */
    private const LEGAL_FORMS = [
        'ООО', 'АО', 'ПАО', 'ЗАО', 'ОАО', 'ИП', 'НКО', 'АНО', 'ФГУП', 'МУП', 'ГУП', 'ТОО',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $name = trim($value);

        $forms = implode('|', array_map('preg_quote', self::LEGAL_FORMS));

        if (preg_match('/^('.$forms.')[\s".«]/ui', $name) || preg_match('/^('.$forms.')$/ui', $name)) {
            $fail('Укажите название без организационно-правовой формы — выберите её в поле выше.');

            return;
        }

        if (preg_match('/[«»"”“]/u', $name)) {
            $fail('Укажите название без кавычек.');
        }
    }
}
