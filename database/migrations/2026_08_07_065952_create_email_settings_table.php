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
        Schema::create('email_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->boolean('enabled')->default(false)->index();
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('encryption')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->string('notification_to')->nullable();
            $table->boolean('auto_reply_enabled')->default(false);
            $table->string('auto_reply_subject')->nullable();
            $table->text('auto_reply_body')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
