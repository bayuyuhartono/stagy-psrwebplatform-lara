<?php

namespace App\Features\Search\Services;

use App\Features\Search\Repositories\SearchRepository;
use App\Models\User;

class SearchService
{
    public function __construct(private SearchRepository $search) {}

    /** @return array{query: string, total: int, results: array<int, array<string, mixed>>} */
    public function search(User $user, string $term, int $limit): array
    {
        $permissions = $user->roles()->with('permissions:id,name')->get()
            ->flatMap->permissions
            ->pluck('name')
            ->unique();
        $results = $this->search->search($term, $limit, $permissions);

        return ['query' => $term, 'total' => $results->count(), 'results' => $results->all()];
    }
}
