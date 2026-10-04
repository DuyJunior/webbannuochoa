<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('title_en', 200)->nullable();
            $table->string('excerpt_en', 500)->nullable();
            $table->text('body_en')->nullable();
        });
        Schema::table('perfumes', function (Blueprint $table) {
            $table->string('name_en')->nullable();
            $table->text('description_en')->nullable();
        });
        Schema::table('categories', fn (Blueprint $table) => $table->string('name_en')->nullable());
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn(['title_en', 'excerpt_en', 'body_en']));
        Schema::table('perfumes', fn (Blueprint $table) => $table->dropColumn(['name_en', 'description_en']));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('name_en'));
    }
};
