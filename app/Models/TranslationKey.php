<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @OA\Schema(
 *      schema="TranslationKey",
 *      type="object",
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="namespace", type="string", example="app"),
 *      @OA\Property(property="key", type="string", example="auth.login"),
 *      @OA\Property(property="description", type="string", example="Login button"),
 *      @OA\Property(
 *          property="values",
 *          type="array",
 *          @OA\Items(ref="#/components/schemas/TranslationValue")
 *      ),
 *      @OA\Property(
 *          property="tags",
 *          type="array",
 *          @OA\Items(type="string", example="web")
 *      )
 * )
 *
 * @OA\Schema(
 *      schema="TranslationValue",
 *      type="object",
 *      @OA\Property(property="locale", type="string", example="en"),
 *      @OA\Property(property="value", type="string", example="Login")
 * )
 */

class TranslationKey extends Model
{
    protected $fillable = ['namespace', 'key', 'description', 'version'];

    public function values(): HasMany
    {
        return $this->hasMany(TranslationValue::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
