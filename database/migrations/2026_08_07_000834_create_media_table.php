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
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableUuidMorphs('mediable');
            $table->string('disk');
            $table->string('path')->unique();
            $table->string('folder')->nullable()->index();
            $table->string('original_name');
            $table->string('mime_type', 150)->index();
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->char('checksum', 64);
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['disk', 'checksum']);
            $table->index(['folder', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
