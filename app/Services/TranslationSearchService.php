<?php

namespace App\Services;

use App\Models\TranslationKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TranslationSearchService
{
    public function search(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $q = TranslationKey::query()->with(['values.locale', 'tags']);

        if (!empty($filters['namespace'])) {
            $q->where('namespace', $filters['namespace']);
        }

        if (!empty($filters['key'])) {
            $q->where('key', 'like', '%' . str_replace('%', '\%', $filters['key']) . '%');
        }

        if (!empty($filters['content'])) {
            // FULLTEXT fallback to LIKE if engine doesn't support it
            $q->whereHas('values', function (Builder $v) use ($filters) {
                $term = $filters['content'];
                $v->whereRaw('MATCH(value) AGAINST (? IN NATURAL LANGUAGE MODE)', [$term])
                    ->orWhere('value', 'like', '%' . str_replace('%', '\%', $term) . '%');
            });
        }

        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : explode(',', $filters['tags']);
            $q->whereHas('tags', fn(Builder $t) => $t->whereIn('slug', $tags));
        }

        if (!empty($filters['locales'])) {
            $codes = is_array($filters['locales']) ? $filters['locales'] : explode(',', $filters['locales']);
            $q->whereHas('values.locale', fn(Builder $l) => $l->whereIn('code', $codes));
        }

        return $q->orderByDesc('updated_at')->paginate($perPage);
    }
}
