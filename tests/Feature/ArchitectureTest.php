<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Enforces the feature-first layering documented in CLAUDE.md:
 * HTTP → Controller → Action → Service → Repository → Model.
 *
 * Rules are scoped to specific namespaces on purpose. A blanket `expect('App')`
 * parses every file in the application and exhausts the default 128M memory
 * limit when the whole suite runs in a single process.
 */
arch('controllers delegate persistence rather than querying directly')
    ->expect('App\Http\Controllers')
    ->not->toUse([DB::class, Builder::class]);

arch('form requests validate and do not persist')
    ->expect('App\Http\Requests')
    ->not->toUse([DB::class, Builder::class]);

arch('policies only authorize')
    ->expect('App\Policies')
    ->not->toUse([DB::class, 'App\Http\Controllers']);

arch('models never reach into HTTP concerns')
    ->expect('App\Models')
    ->not->toUse(['App\Http\Controllers', 'App\Http\Requests', Request::class]);

arch('resources transform and nothing more')
    ->expect('App\Http\Resources')
    ->not->toUse([DB::class, 'App\Http\Controllers']);

arch('feature enums are string backed')
    ->expect('App\Features')
    ->enums()
    ->toBeStringBackedEnums();

arch('feature code compares strictly')
    ->expect('App\Features')
    ->toUseStrictEquality();

arch('no debugging helpers ship to production')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();
