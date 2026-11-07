<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('seoable');
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 170)->nullable();
            $table->string('focus_keyword')->nullable()->index();
            $table->string('canonical_url', 2048)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->string('og_title', 70)->nullable();
            $table->string('og_description', 200)->nullable();
            $table->foreignUuid('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('twitter_card')->default('summary_large_image');
            $table->json('structured_data')->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seos');
    }
};
