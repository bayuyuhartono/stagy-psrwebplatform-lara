<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\ContentBlocks\ContentBlockRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentBlockSchemaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ContentBlockRegistry $registry): JsonResponse
    {
        abort_unless(collect([
            'pages.view', 'pages.create', 'pages.update',
            'posts.create', 'posts.update',
            'services.create', 'services.update',
            'portfolio.create', 'portfolio.update',
        ])->contains(fn (string $permission): bool => $request->user()->hasPermission($permission)), 403);

        return response()->json(['data' => $registry->definitions()]);
    }
}
