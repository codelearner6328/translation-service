<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Models\Locale;
use App\Models\Tag;
use App\Models\TranslationKey;
use App\Models\TranslationValue;
use App\Services\TranslationExportService;
use App\Services\TranslationSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TranslationController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/translations",
     *      operationId="getTranslations",
     *      tags={"Translations"},
     *      summary="List translations with filters",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="namespace", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="key", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="tags", in="query", @OA\Schema(type="string", example="web,mobile")),
     *      @OA\Response(
     *          response=200,
     *          description="List of translations",
     *          @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/TranslationKey"))
     *      )
     * )
     */
    public function index(Request $request, TranslationSearchService $service)
    {
        $filters = $request->only('namespace', 'key', 'content', 'tags', 'locales');
        $perPage = (int) $request->query('per_page', 25);
        $data = $service->search($filters, $perPage);

        return response()->json($data);
    }

    /**
     * @OA\Post(
     *      path="/api/translations",
     *      operationId="createTranslation",
     *      tags={"Translations"},
     *      summary="Create new translation",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"key","values"},
     *              @OA\Property(property="namespace", type="string", example="app"),
     *              @OA\Property(property="key", type="string", example="auth.login"),
     *              @OA\Property(property="description", type="string", example="Login button"),
     *              @OA\Property(
     *                  property="values",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="locale", type="string", example="en"),
     *                      @OA\Property(property="value", type="string", example="Login")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Translation created",
     *          @OA\JsonContent(ref="#/components/schemas/TranslationKey")
     *      )
     * )
     */
    public function store(StoreTranslationRequest $request)
    {
        $payload = $request->validated();

        return DB::transaction(function () use ($payload) {
            $tk = TranslationKey::create([
                'namespace'   => $payload['namespace'] ?? 'app',
                'key'         => $payload['key'],
                'description' => $payload['description'] ?? null,
            ]);

            // Tags
            foreach ($payload['tags'] ?? [] as $slug) {
                $tag = Tag::firstOrCreate(['slug' => $slug], ['label' => $slug]);
                $tk->tags()->attach($tag->id);
            }

            // Locales & values
            foreach ($payload['values'] as $v) {
                $locale = Locale::firstOrCreate(['code' => $v['locale']], ['name' => strtoupper($v['locale'])]);
                TranslationValue::create([
                    'translation_key_id' => $tk->id,
                    'locale_id' => $locale->id,
                    'value' => $v['value'],
                ]);
            }

            return response()->json($tk->load(['values.locale', 'tags']), 201);
        });
    }
    /**
     * @OA\Get(
     *      path="/api/translations/{id}",
     *      operationId="getTranslationById",
     *      tags={"Translations"},
     *      summary="Get a translation by ID",
     *      description="Returns a single translation including values and tags",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer"),
     *          description="Translation ID"
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Translation details",
     *          @OA\JsonContent(ref="#/components/schemas/TranslationKey")
     *      ),
     *      @OA\Response(response=404, description="Translation not found")
     * )
     */
    public function show(TranslationKey $translationKey)
    {
        return response()->json($translationKey->load(['values.locale', 'tags']));
    }
    /**
     * @OA\Put(
     *      path="/api/translations/{id}",
     *      operationId="updateTranslation",
     *      tags={"Translations"},
     *      summary="Update an existing translation",
     *      description="Updates namespace, key, description, tags, and values of a translation",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer"),
     *          description="Translation ID"
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="namespace", type="string", example="app"),
     *              @OA\Property(property="key", type="string", example="auth.login"),
     *              @OA\Property(property="description", type="string", example="Login button"),
     *              @OA\Property(
     *                  property="tags",
     *                  type="array",
     *                  @OA\Items(type="string", example="web")
     *              ),
     *              @OA\Property(
     *                  property="values",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="locale", type="string", example="fr"),
     *                      @OA\Property(property="value", type="string", example="Connexion")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Updated translation",
     *          @OA\JsonContent(ref="#/components/schemas/TranslationKey")
     *      ),
     *      @OA\Response(response=404, description="Translation not found"),
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(UpdateTranslationRequest $request, TranslationKey $translationKey)
    {
        $payload = $request->validated();

        return DB::transaction(function () use ($payload, $translationKey) {
            if (isset($payload['namespace']) || isset($payload['key']) || array_key_exists('description', $payload)) {
                $translationKey->fill([
                    'namespace'   => $payload['namespace'] ?? $translationKey->namespace,
                    'key'         => $payload['key'] ?? $translationKey->key,
                    'description' => $payload['description'] ?? $translationKey->description,
                ])->save();
            }

            if (isset($payload['tags'])) {
                $tagIds = [];
                foreach ($payload['tags'] as $slug) {
                    $tagIds[] = Tag::firstOrCreate(['slug' => $slug], ['label' => $slug])->id;
                }
                $translationKey->tags()->sync($tagIds);
            }

            if (isset($payload['values'])) {
                foreach ($payload['values'] as $v) {
                    $locale = Locale::firstOrCreate(['code' => $v['locale']], ['name' => strtoupper($v['locale'])]);
                    TranslationValue::updateOrCreate(
                        ['translation_key_id' => $translationKey->id, 'locale_id' => $locale->id],
                        ['value' => $v['value']]
                    );
                }
            }

            // bump version for cache-busting
            $translationKey->increment('version');

            return response()->json($translationKey->load(['values.locale', 'tags']));
        });
    }

    /**
     * @OA\Delete(
     *      path="/api/translations/{id}",
     *      operationId="deleteTranslation",
     *      tags={"Translations"},
     *      summary="Delete a translation",
     *      description="Deletes a translation by ID",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer"),
     *          description="Translation ID"
     *      ),
     *      @OA\Response(response=204, description="Translation deleted"),
     *      @OA\Response(response=404, description="Translation not found")
     * )
     */
    public function destroy(TranslationKey $translationKey)
    {
        $translationKey->delete();
        return response()->noContent();
    }


    /**
     * @OA\Get(
     *      path="/api/translations/export",
     *      operationId="exportTranslations",
     *      tags={"Translations"},
     *      summary="Export translations in JSON",
     *      @OA\Parameter(name="locales", in="query", @OA\Schema(type="string", example="en,fr")),
     *      @OA\Parameter(name="tags", in="query", @OA\Schema(type="string", example="mobile")),
     *      @OA\Response(
     *          response=200,
     *          description="Exported JSON",
     *          @OA\JsonContent(
     *              type="object",
     *              example={"auth.login": {"en": "Login", "fr": "Connexion"}}
     *          )
     *      )
     * )
     */
    public function export(Request $request, TranslationExportService $service)
    {
        return $service->streamExport($request);
    }
}
