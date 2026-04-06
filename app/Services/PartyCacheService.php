<?php

namespace App\Services;

use App\Models\Party;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PartyCacheService
{
    private const ALL_PARTIES_KEY = 'parties:all:v1';

    private const UNASSIGNED_PARTIES_KEY = 'parties:unassigned:v1';

    private const TTL_MINUTES = 30;

    public function all(): Collection
    {
        return Cache::remember(
            self::ALL_PARTIES_KEY,
            now()->addMinutes(self::TTL_MINUTES),
            fn () => Party::query()
                ->select(['id', 'name', 'phone', 'address'])
                ->orderBy('name')
                ->get()
        );
    }

    public function unassignedForEmployees(): Collection
    {
        return Cache::remember(
            self::UNASSIGNED_PARTIES_KEY,
            now()->addMinutes(self::TTL_MINUTES),
            fn () => Party::query()
                ->select(['id', 'name', 'phone', 'address'])
                ->whereDoesntHave('employees')
                ->orderBy('name')
                ->get()
        );
    }

    public function refreshAll(): void
    {
        Cache::forget(self::ALL_PARTIES_KEY);
        Cache::forget(self::UNASSIGNED_PARTIES_KEY);

        $this->all();
        $this->unassignedForEmployees();
    }

    public function refreshUnassigned(): void
    {
        Cache::forget(self::UNASSIGNED_PARTIES_KEY);

        $this->unassignedForEmployees();
    }
}
