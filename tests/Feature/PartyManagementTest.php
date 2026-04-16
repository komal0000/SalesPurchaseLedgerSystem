<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_index_filters_by_category_and_keyword(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $employeeParty = Party::query()->create([
            'name' => 'Alpha Employee Party',
            'phone' => '9800000001',
            'address' => 'Kathmandu',
        ]);

        $ordinaryParty = Party::query()->create([
            'name' => 'Beta Ordinary Party',
            'phone' => '9800000002',
            'address' => 'Pokhara',
        ]);

        Employee::query()->create([
            'party_id' => $employeeParty->id,
            'salary' => 1000,
        ]);

        $employeeFilter = $this->actingAs($user)
            ->get(route('parties.index', ['category' => 'employee']));

        $employeeFilter
            ->assertOk()
            ->assertViewHas('parties', function ($parties) use ($employeeParty, $ordinaryParty): bool {
                $ids = collect($parties->items())->pluck('id');

                return $ids->contains($employeeParty->id)
                    && !$ids->contains($ordinaryParty->id);
            });

        $ordinaryFilter = $this->actingAs($user)
            ->get(route('parties.index', ['category' => 'ordinary']));

        $ordinaryFilter
            ->assertOk()
            ->assertViewHas('parties', function ($parties) use ($employeeParty, $ordinaryParty): bool {
                $ids = collect($parties->items())->pluck('id');

                return $ids->contains($ordinaryParty->id)
                    && !$ids->contains($employeeParty->id);
            });

        $keywordFilter = $this->actingAs($user)
            ->get(route('parties.index', ['keyword' => 'Pokhara']));

        $keywordFilter
            ->assertOk()
            ->assertViewHas('parties', function ($parties) use ($employeeParty, $ordinaryParty): bool {
                $ids = collect($parties->items())->pluck('id');

                return $ids->contains($ordinaryParty->id)
                    && !$ids->contains($employeeParty->id);
            });
    }

    public function test_party_can_be_updated_from_edit_page(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $party = Party::query()->create([
            'name' => 'Original Party',
            'phone' => '9800000091',
            'address' => 'Biratnagar',
            'opening_balance' => 100,
            'opening_balance_side' => 'dr',
        ]);

        $response = $this->actingAs($user)
            ->put(route('parties.update', $party), [
                'name' => 'Updated Party',
                'phone' => '9800000092',
                'address' => 'Lalitpur',
                'opening_balance' => 250.50,
                'opening_balance_side' => 'cr',
            ]);

        $response
            ->assertRedirect(route('parties.show', $party))
            ->assertSessionHas('success', 'Party updated successfully.');

        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'name' => 'Updated Party',
            'phone' => '9800000092',
            'address' => 'Lalitpur',
            'opening_balance_side' => 'cr',
        ]);
    }

    public function test_party_delete_is_blocked_when_linked_records_exist(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 0]);

        $party = Party::query()->create([
            'name' => 'Protected Employee Party',
            'phone' => '9800000031',
        ]);

        Employee::query()->create([
            'party_id' => $party->id,
            'salary' => 500,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('parties.destroy', $party));

        $response
            ->assertRedirect(route('parties.index'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'Employees'));

        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
        ]);
    }
}
