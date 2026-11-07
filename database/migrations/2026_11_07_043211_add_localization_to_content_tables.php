<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose rows are written once per locale and linked by a translation group.
     *
     * Each entry maps the table to the column that must be unique within a locale.
     *
     * @var array<string, string>
     */
    private array $translatableTables = [
        'pages' => 'slug',
        'posts' => 'slug',
        'categories' => 'slug',
        'menus' => 'location',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultLocale = config('localization.default', 'id');

        foreach ($this->translatableTables as $table => $uniqueColumn) {
            Schema::table($table, function (Blueprint $blueprint) use ($defaultLocale): void {
                $blueprint->string('locale', 8)->default($defaultLocale)->index();
                $blueprint->uuid('translation_group_id')->nullable();
            });

            // Existing rows start as the only member of their own translation group.
            DB::table($table)->update(['translation_group_id' => DB::raw('id')]);

            Schema::table($table, function (Blueprint $blueprint) use ($table, $uniqueColumn): void {
                $blueprint->dropUnique("{$table}_{$uniqueColumn}_unique");
                $blueprint->unique(['locale', $uniqueColumn]);
                $blueprint->unique(['translation_group_id', 'locale']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->translatableTables as $table => $uniqueColumn) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $uniqueColumn): void {
                $blueprint->dropUnique("{$table}_translation_group_id_locale_unique");
                $blueprint->dropUnique("{$table}_locale_{$uniqueColumn}_unique");
                $blueprint->dropIndex("{$table}_locale_index");
                $blueprint->dropColumn(['locale', 'translation_group_id']);
                $blueprint->unique($uniqueColumn);
            });
        }
    }
};
