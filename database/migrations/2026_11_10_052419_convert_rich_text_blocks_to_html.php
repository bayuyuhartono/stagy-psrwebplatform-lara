<?php

use App\Features\ContentBlocks\Services\RichTextContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rich text blocks used to hold Markdown; the CMS editor now writes HTML.
 * Convert every stored block (trashed rows included) to sanitized HTML.
 * Writes go through the query builder so no audit entries or timestamps change.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['pages', 'posts', 'services', 'portfolios'];

    public function up(): void
    {
        $richText = app(RichTextContent::class);

        foreach ($this->tables as $table) {
            DB::table($table)->select(['id', 'blocks'])->orderBy('id')->chunk(100, function ($rows) use ($table, $richText): void {
                foreach ($rows as $row) {
                    $blocks = json_decode((string) $row->blocks, true);

                    if (! is_array($blocks)) {
                        continue;
                    }

                    $normalized = $richText->normalizeBlocks($blocks);

                    if ($normalized !== $blocks) {
                        DB::table($table)->where('id', $row->id)->update(['blocks' => json_encode($normalized)]);
                    }
                }
            });
        }
    }

    /**
     * HTML cannot be turned back into the original Markdown; the HTML still renders.
     */
    public function down(): void {}
};
