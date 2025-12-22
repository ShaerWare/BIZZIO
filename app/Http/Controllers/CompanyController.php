<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies.
     */
    public function index(Request $request)
    {
        $query = Company::with(['industry', 'creator', 'moderators']);

        // Фильтр по отрасли
        if ($request->filled('industry_id')) {
            $query->where('industry_id', $request->industry_id);
        }

        // Фильтр по верификации
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        // Поиск по названию или ИНН
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('inn', 'like', "%{$search}%");
            });
        }

        $companies = $query->paginate(20);
        $industries = Industry::orderBy('name')->get();

        return view('companies.index', compact('companies', 'industries'));
    }

    /**
     * Show the form for creating a new company.
     */
    public function create()
    {
        $industries = Industry::orderBy('name')->get();
        return view('companies.create', compact('industries'));
    }

    /**
     * Store a newly created company.
     */
    public function store(StoreCompanyRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        // Загрузка логотипа
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $company = Company::create($data);

        // Загрузка документов (PDF)
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $company->addMedia($document)->toMediaCollection('documents');
            }
        }

        // Назначить создателя владельцем
        $company->assignModerator(auth()->user(), 'owner');

        return redirect()->route('companies.show', $company)
            ->with('success', 'Компания успешно создана!');
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company)
    {
        $company->load([
        'industry', 
        'creator', 
        'moderators',
        'joinRequests' => function ($query) {
            $query->where('status', 'pending')
                  ->with('user')
                  ->orderBy('created_at', 'desc');
            }
        ]);
        
        return view('companies.show', compact('company'));
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit(Company $company)
    {
        // Проверка прав доступа
        if ($company->created_by !== auth()->id() && !$company->isModerator(auth()->user())) {
            abort(403, 'У вас нет прав для редактирования этой компании');
        }

        $industries = Industry::orderBy('name')->get();
        return view('companies.edit', compact('company', 'industries'));
    }

    /**
     * Update the specified company.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $data = $request->validated();

        // Загрузка нового логотипа
        if ($request->hasFile('logo')) {
            // Удаляем старый логотип
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $company->update($data);

        // Добавление новых документов
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $company->addMedia($document)->toMediaCollection('documents');
            }
        }

        return redirect()->route('companies.show', $company)
            ->with('success', 'Компания успешно обновлена!');
    }

    /**
     * Remove the specified company.
     */
    public function destroy(Company $company)
    {
        // Только создатель может удалить компанию
        if ($company->created_by !== auth()->id()) {
            abort(403, 'У вас нет прав для удаления этой компании');
        }

        // Удаление логотипа
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Компания успешно удалена!');
    }
}