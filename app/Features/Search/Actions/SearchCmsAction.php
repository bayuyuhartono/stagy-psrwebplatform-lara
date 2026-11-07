<?php

namespace App\Features\Search\Actions;

use App\Features\Search\Services\SearchService;
use App\Models\User;

class SearchCmsAction
{
    public function __construct(private SearchService $service) {}

    /** @return array{query: string, total: int, results: array<int, array<string, mixed>>} */
    public function handle(User $user, string $term, int $limit): array
    {
        return $this->service->search($user, $term, $limit);
    }
}
