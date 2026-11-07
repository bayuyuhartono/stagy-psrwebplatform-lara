<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A demo request is a contact message with event details. Its visitor may
     * leave only a WhatsApp number, so email and message become optional.
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('form_type', 20)->default('contact')->index();
            $table->string('locale', 8)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('guest_estimate', 20)->nullable()->index();
            $table->date('event_date')->nullable()->index();
            $table->string('interested_plan', 80)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->string('email')->nullable()->change();
            $table->text('message')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['form_type']);
            $table->dropIndex(['guest_estimate']);
            $table->dropIndex(['event_date']);
            $table->dropColumn(['form_type', 'locale', 'whatsapp', 'guest_estimate', 'event_date', 'interested_plan', 'consented_at']);
            $table->string('email')->nullable(false)->change();
            $table->text('message')->nullable(false)->change();
        });
    }
};
