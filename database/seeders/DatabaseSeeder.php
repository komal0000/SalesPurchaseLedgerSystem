<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Party;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Account::query()->create([
            'name' => 'Cash',
            'type' => 'cash',
        ]);

        Account::query()->create([
            'name' => 'Bank',
            'type' => 'bank',
        ]);

        foreach (['Ram Traders', 'Sita Suppliers', 'Hari Shop'] as $name) {
            Party::query()->create([
                'name' => $name,
                'phone' => null,
            ]);
        }
    }
}
