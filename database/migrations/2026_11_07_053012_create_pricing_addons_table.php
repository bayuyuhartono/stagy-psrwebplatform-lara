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
        Schema::create('pricing_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('locale', 8)->index();
            $table->uuid('translation_group_id');
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->string('unit')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['translation_group_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_addons');
    }
};
