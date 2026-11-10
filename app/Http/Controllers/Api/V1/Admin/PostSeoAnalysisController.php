<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Seo\Services\ContentSeoAnalyzer;
use App\Features\WebsiteSettings\Services\PublicWebsiteSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AnalyzePostSeoRequest;
use Illuminate\Http\JsonResponse;

class PostSeoAnalysisController extends Controller
{
    /**
     * Analyze the editor's current, unsaved post for the live SEO checklist.
     */
    public function __invoke(AnalyzePostSeoRequest $request, ContentSeoAnalyzer $analyzer, PublicWebsiteSettings $settings): JsonResponse
    {
        return response()->json(['data' => $analyzer->analyze([
            ...$request->validated(),
            'site_name' => $settings->forCurrentLocale()->get('general', [])['site_name'] ?? config('app.name'),
        ])]);
    }
}
