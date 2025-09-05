<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class TranslationKey extends Model
{
    protected $fillable = ['namespace','key','description','version'];

    public function values(): HasMany
    {
        return $this->hasMany(TranslationValue::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
