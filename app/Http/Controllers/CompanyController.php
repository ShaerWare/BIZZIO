<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Companies",
 *     description="Управление компаниями"
 * )
 */
class CompanyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies",
     *     tags={"Companies"},
     *     summary="Получить список компаний",
     *     description="Возвращает пагинированный список компаний с фильтрами",
     *     @OA\Parameter(
     *         name="industry_id",
     *         in="query",
     *         description="ID отрасли",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_verified",
     *         in="query",
     *         description="Фильтр по верификации (0 или 1)",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Поиск по названию или ИНН",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Company"))
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::with(['industry', 'creator', 'moderators']);

        // Фильтр по отрасли
        if ($request->has('industry_id')) {
            $query->where('industry_id', $request->industry_id);
        }

        // Фильтр по верификации
        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        // Поиск по названию или ИНН
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('inn', 'like', "%{$search}%");
            });
        }

        $companies = $query->paginate(20);

        return response()->json([
            'data' => CompanyResource::collection($companies),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/companies",
     *     tags={"Companies"},
     *     summary="Создать компанию",
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "inn"},
     *             @OA\Property(property="name", type="string", example="ООО Рога и Копыта"),
     *             @OA\Property(property="inn", type="string", example="123456789012"),
     *             @OA\Property(property="legal_form", type="string", example="ООО"),
     *             @OA\Property(property="short_description", type="string"),
     *             @OA\Property(property="full_description", type="string"),
     *             @OA\Property(property="industry_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Компания создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Company")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации"),
     *     @OA\Response(response=401, description="Не авторизован")
     * )
     */
    public function store(StoreCompanyRequest $request): JsonResponse
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

        // Назначить создателя модератором
        $company->assignModerator(auth()->user(), 'owner');

        return response()->json([
            'data' => new CompanyResource($company->load(['industry', 'creator', 'moderators']))
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{id}",
     *     tags={"Companies"},
     *     summary="Получить компанию по ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Company")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Компания не найдена")
     * )
     */
    public function show(Company $company): JsonResponse
    {
        return response()->json([
            'data' => new CompanyResource($company->load(['industry', 'creator', 'moderators']))
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/companies/{id}",
     *     tags={"Companies"},
     *     summary="Обновить компанию",
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="inn", type="string"),
     *             @OA\Property(property="short_description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Компания обновлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Company")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Доступ запрещён"),
     *     @OA\Response(response=404, description="Компания не найдена"),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
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

        return response()->json([
            'data' => new CompanyResource($company->load(['industry', 'creator', 'moderators']))
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/companies/{id}",
     *     tags={"Companies"},
     *     summary="Удалить компанию",
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Компания удалена"),
     *     @OA\Response(response=403, description="Доступ запрещён"),
     *     @OA\Response(response=404, description="Компания не найдена")
     * )
     */
    public function destroy(Company $company): JsonResponse
    {
        // Только создатель может удалить компанию
        if ($company->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $company->delete();

        return response()->json(null, 204);
    }
}