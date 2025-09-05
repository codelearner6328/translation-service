<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphedByMany;

class Tag extends Model
{
    protected $fillable = ['slug','label'];

    public function translationKeys(): MorphedByMany
    {
        return $this->morphedByMany(TranslationKey::class, 'taggable');
    }
}
