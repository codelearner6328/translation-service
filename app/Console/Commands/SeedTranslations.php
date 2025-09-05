<?php

namespace App\Console\Commands;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\TranslationKey;
use App\Models\TranslationValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedTranslations extends Command
{
    protected $signature = 'translations:seed {--count=100000} {--locales=en,fr,es} {--tags=mobile,desktop,web}';
    protected $description = 'Seed translations in bulk for performance testing';

    public function handle(): int
    {
        $count   = (int) $this->option('count');
        $locales = array_map('trim', explode(',', $this->option('locales')));
        $tags    = array_map('trim', explode(',', $this->option('tags')));

        // Ensure locales & tags
        $localeMap = [];
        foreach ($locales as $code) {
            $localeMap[$code] = Locale::firstOrCreate(['code' => $code], ['name' => strtoupper($code)])->id;
        }
        $tagIds = [];
        foreach ($tags as $slug) {
            $tagIds[] = Tag::firstOrCreate(['slug' => $slug], ['label' => Str::title($slug)])->id;
        }

        $chunk = 1000;
        $this->info("Seeding {$count} keys (chunk {$chunk}) with " . count($locales) . " locales…");

        for ($i = 0; $i < $count; $i += $chunk) {
            $keysData = [];
            $now = now();
            for ($j = 0; $j < $chunk && $i + $j < $count; $j++) {
                $keysData[] = [
                    'namespace' => ['app', 'auth', 'email', 'shop'][array_rand(['a', 'b', 'c', 'd'])],
                    'key' => 'key_' . ($i + $j),
                    'description' => 'Auto generated ' . $i . $j,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('translation_keys')->insert($keysData);

            // fetch inserted IDs
            $startId = DB::getPdo()->lastInsertId();
            $startId = (int) $startId - (count($keysData) - 1);

            // pivot tags & values
            $taggables = [];
            $values = [];
            $id = $startId;
            foreach ($keysData as $_) {
                foreach ($tagIds as $tagId) {
                    if (mt_rand(0, 1)) $taggables[] = [
                        'tag_id' => $tagId,
                        'taggable_id' => $id,
                        'taggable_type' => TranslationKey::class,
                    ];
                }
                foreach ($localeMap as $code => $localeId) {
                    $values[] = [
                        'translation_key_id' => $id,
                        'locale_id' => $localeId,
                        'value' => ucfirst(str_replace('_', ' ', 'value_' . $code . '_' . $id)),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                $id++;
            }
            DB::table('taggables')->insert($taggables);
            foreach (array_chunk($values, 2000) as $vchunk) {
                DB::table('translation_values')->insert($vchunk);
            }

            $this->info("Inserted " . min($i + $chunk, $count) . " / {$count}");
        }

        return self::SUCCESS;
    }
}
