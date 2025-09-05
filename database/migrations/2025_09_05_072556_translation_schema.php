<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('translation_keys', function (Blueprint $table) {
            $table->id();
            $table->string('namespace')->default('app');      // optional grouping
            $table->string('key');                            // e.g., "auth.login"
            $table->string('description')->nullable();
            $table->unsignedBigInteger('version')->default(1); // bump on any change
            $table->timestamps();
            $table->unique(['namespace','key']);
            $table->index('updated_at');
        });

        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // en, fr, es-ES
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('translation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained()->cascadeOnDelete();
            $table->text('value');
            $table->timestamps();

            $table->unique(['translation_key_id','locale_id']);
            $table->index(['locale_id','updated_at']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // mobile, desktop, web
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable'); // {taggable_type, taggable_id}
            $table->index(['tag_id','taggable_type']);
        });

        // MySQL FULLTEXT for searching keys & values fast
        Schema::table('translation_keys', function (Blueprint $table) {
            $table->fullText(['key','description']);
        });
        Schema::table('translation_values', function (Blueprint $table) {
            $table->fullText(['value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('translation_values');
        Schema::dropIfExists('locales');
        Schema::dropIfExists('translation_keys');
    }
};
