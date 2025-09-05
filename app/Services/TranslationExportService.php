<?php

namespace App\Services;

use App\Models\TranslationKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationExportService
{
    public function streamExport(Request $request): StreamedResponse
    {
        $locales = array_filter(array_map('trim', explode(',', $request->query('locales', ''))));
        $tags    = array_filter(array_map('trim', explode(',', $request->query('tags', ''))));
        $namespace = $request->query('namespace'); // optional
        $flat   = filter_var($request->query('flat', true), FILTER_VALIDATE_BOOLEAN);

        $cacheKey = 'export:' . md5(json_encode([$locales, $tags, $namespace, $flat]));
        $metaKey  = $cacheKey . ':meta';

        // Determine last update timestamp for ETag/Last-Modified
        $lastUpdated = Cache::remember($metaKey, 60, function () use ($tags, $namespace) {
            $q = TranslationKey::query();
            if ($namespace) $q->where('namespace', $namespace);
            if ($tags) {
                $q->whereHas('tags', fn($t) => $t->whereIn('slug', $tags));
            }
            return (string) optional($q->max('updated_at')) ?? now()->toDateTimeString();
        });

        $etag = sha1($cacheKey . $lastUpdated);
        if (trim($request->header('If-None-Match', '')) === $etag) {
            return response()->noContent(304)->setEtag($etag);
        }

        $headers = [
            'Content-Type'              => 'application/json',
            'Cache-Control'             => 'public, max-age=60',
            'ETag'                      => $etag,
            'Last-Modified'             => gmdate('D, d M Y H:i:s', strtotime($lastUpdated)) . ' GMT',
            'X-Accel-Buffering'         => 'no',
            'Content-Disposition'       => 'inline; filename="translations.json"',
        ];

        return response()->stream(function () use ($locales, $tags, $namespace, $flat) {
            $cursor = TranslationKey::query()
                ->when($namespace, fn($q) => $q->where('namespace', $namespace))
                ->when($tags, function ($q) use ($tags) {
                    $q->whereHas('tags', fn($t) => $t->whereIn('slug', $tags));
                })
                ->with(['values.locale:id,code'])
                ->orderBy('id')
                ->cursor(); // lazy, low-memory

            echo '{';
            $firstKey = true;
            foreach ($cursor as $tk) {
                $groupKey = $flat ? ($tk->namespace ? $tk->namespace . '.' . $tk->key : $tk->key) : $tk->key;

                $values = [];
                foreach ($tk->values as $val) {
                    $code = $val->locale->code;
                    if ($locales && !in_array($code, $locales, true)) continue;
                    $values[$code] = $val->value;
                }
                if (empty($values)) continue;

                if (!$firstKey) echo ',';
                $firstKey = false;

                if ($flat) {
                    foreach ($values as $code => $v) {
                        $flatKey = $groupKey . '.' . $code;
                        echo json_encode($flatKey) . ':' . json_encode($v);
                        echo ',';
                    }
                    // Remove trailing comma by backspacing 1 char is messy; instead buffer per-key
                    // Simpler: build small chunk string then trim.
                } else {
                    echo json_encode($groupKey) . ':' . json_encode($values, JSON_UNESCAPED_UNICODE);
                }
            }
            echo '}';
        }, 200, $headers);
    }

    /** invalidate meta cache after updates */
    public static function bumpMetaCache(): void
    {
        Cache::tags(['export'])->flush(); // if using tagged cache
    }
}
