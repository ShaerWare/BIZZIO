<?php

namespace App\Orchid\Screens;

use App\Models\Company;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class CompanyEditScreen extends Screen
{
    public ?Company $company = null;

    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(Company $company): iterable
    {
        $company->load(['industry', 'creator', 'moderators']);

        return [
            'company' => $company,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->company->exists ? 'Редактирование компании' : 'Создание компании';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return $this->company->exists
            ? 'Редактирование информации о компании: ' . $this->company->name
            : 'Создание новой компании';
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Сохранить')
                ->icon('check')
                ->method('save'),

            Button::make('Удалить')
                ->icon('trash')
                ->method('remove')
                ->canSee($this->company->exists)
                ->confirm('Вы уверены, что хотите удалить эту компанию?'),
        ];
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('company.name')
                    ->title('Название компании')
                    ->placeholder('ООО "Рога и Копыта"')
                    ->required()
                    ->help('Полное название компании'),

                Input::make('company.inn')
                    ->title('ИНН')
                    ->mask('999999999999')
                    ->placeholder('123456789012')
                    ->required()
                    ->help('ИНН должен содержать 12 цифр'),

                Input::make('company.legal_form')
                    ->title('Организационно-правовая форма')
                    ->placeholder('ООО, АО, ИП')
                    ->help('Например: ООО, АО, ИП, ПАО'),

                Relation::make('company.industry_id')
                    ->title('Отрасль')
                    ->fromModel(Industry::class, 'name')
                    ->empty('Не выбрано')
                    ->help('Выберите отрасль деятельности'),

                TextArea::make('company.short_description')
                    ->title('Краткое описание')
                    ->rows(3)
                    ->maxlength(500)
                    ->help('Максимум 500 символов'),

                TextArea::make('company.full_description')
                    ->title('Полное описание')
                    ->rows(6)
                    ->help('Подробное описание компании'),

                Upload::make('company.logo')
                    ->title('Логотип')
                    ->acceptedFiles('image/*')
                    ->maxFiles(1)
                    ->help('Форматы: JPG, PNG. Максимальный размер: 2MB'),

                Upload::make('company.documents')
                    ->title('Документы (PDF)')
                    ->acceptedFiles('.pdf')
                    ->maxFiles(10)
                    ->help('Устав, ИНН, ОГРН и другие документы. Максимум 10 файлов по 10MB'),

                CheckBox::make('company.is_verified')
                    ->title('Верифицирована')
                    ->placeholder('Компания прошла проверку')
                    ->help('Только администратор может верифицировать компанию')
                    ->canSee(auth()->user()->hasAccess('platform.systems.roles')),

                Relation::make('company.moderators.')
                    ->title('Модераторы компании')
                    ->fromModel(User::class, 'name')
                    ->multiple()
                    ->help('Пользователи, которые могут управлять компанией'),
            ]),
        ];
    }

    /**
     * Save company
     */
    public function save(Company $company, Request $request)
    {
        $data = $request->get('company');

        // Если создание новой компании
        if (!$company->exists) {
            $data['created_by'] = auth()->id();
        }

        $company->fill($data)->save();

        // Синхронизация модераторов
        if ($request->has('company.moderators')) {
            $moderators = $request->input('company.moderators', []);
            $company->moderators()->sync($moderators);
        }

        Alert::success('Компания успешно сохранена');

        return redirect()->route('platform.companies.list');
    }

    /**
     * Remove company
     */
    public function remove(Company $company)
    {
        $company->delete();

        Alert::success('Компания успешно удалена');

        return redirect()->route('platform.companies.list');
    }
}