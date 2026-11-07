<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Search\Actions\SearchCmsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\GlobalSearchRequest;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(GlobalSearchRequest $request, SearchCmsAction $search): JsonResponse
    {
        return response()->json([
            'data' => $search->handle(
                $request->user(),
                $request->validated('q'),
                $request->integer('limit', 20),
            ),
        ]);
    }
}
