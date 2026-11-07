<?php

namespace App\Http\Controllers\Public;

use App\Features\Seo\Services\RobotsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RobotsService $robots): Response
    {
        return response($robots->content(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
