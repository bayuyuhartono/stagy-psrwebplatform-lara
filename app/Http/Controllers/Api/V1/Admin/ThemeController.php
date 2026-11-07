<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Themes\DataTransferObjects\ThemeManifest;
use App\Features\Themes\Services\ThemeManager;
use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ThemeController extends Controller
{
    /**
     * The installed themes, plus the active theme's settings schema and current values.
     */
    public function index(ThemeManager $themes): JsonResponse
    {
        Gate::authorize('viewAny', WebsiteSetting::class);

        $active = $themes->active();
        $stored = WebsiteSetting::query()->where('group', 'theme')->value('settings') ?? [];

        return response()->json([
            'data' => [
                'active' => $this->manifestPayload($active),
                'settings' => $themes->settingsFor($active, $stored),
                'installed' => array_values(array_map(
                    fn (ThemeManifest $manifest): array => $this->manifestPayload($manifest),
                    $themes->all(),
                )),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function manifestPayload(ThemeManifest $manifest): array
    {
        return [
            'slug' => $manifest->slug,
            'name' => $manifest->name,
            'version' => $manifest->version,
            'author' => $manifest->author,
            'description' => $manifest->description,
            'settings_schema' => $manifest->settings,
        ];
    }
}
