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
    public function index(Request $request, TranslationSearchService $service)
    {
        $filters = $request->only('namespace', 'key', 'content', 'tags', 'locales');
        $perPage = (int) $request->query('per_page', 25);
        $data = $service->search($filters, $perPage);

        return response()->json($data);
    }

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

    public function show(TranslationKey $translationKey)
    {
        return response()->json($translationKey->load(['values.locale', 'tags']));
    }

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

    public function destroy(TranslationKey $translationKey)
    {
        $translationKey->delete();
        return response()->noContent();
    }

    public function export(Request $request, TranslationExportService $service)
    {
        return $service->streamExport($request);
    }
}
