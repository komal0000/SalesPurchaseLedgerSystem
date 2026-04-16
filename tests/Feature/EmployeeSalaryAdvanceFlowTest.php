<?php

namespace Tests\Feature;

use App\Helpers\DateHelper;
use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeSalaryAdvance;
use App\Models\Party;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSalaryAdvanceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_employee_advance_creates_payment_and_advance_record(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        [$employee, $account] = $this->createEmployeeAndAccount('Advance Flow Employee', '9800000801', 18000);
        $advanceDateBs = DateHelper::getCurrentBS();

        $this->actingAs($user)
            ->post(route('employee-advances.store'), [
                'employee_id' => $employee->id,
                'account_id' => $account->id,
                'advance_date_bs' => $advanceDateBs,
                'amount' => 2500,
                'remarks' => 'Advance for emergency',
            ])
            ->assertRedirect();

        $advance = EmployeeSalaryAdvance::query()->firstOrFail();
        $payment = Payment::query()->findOrFail($advance->payment_id);

        $this->assertSame((int) $employee->id, (int) $advance->employee_id);
        $this->assertSame((int) $employee->party_id, (int) $advance->party_id);
        $this->assertSame(substr($advanceDateBs, 0, 7), $advance->salary_month);
        $this->assertSame('2500.00', (string) $advance->amount);

        $this->assertSame('advance', $payment->payment_kind);
        $this->assertSame('paid', $payment->advance_direction);
        $this->assertSame('given', $payment->type);
        $this->assertSame('2500.00', (string) $payment->amount);
    }

    public function test_store_employee_advance_blocks_amount_above_monthly_salary_cap(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        [$employee, $account] = $this->createEmployeeAndAccount('Advance Cap Employee', '9800000802', 10000);
        $salaryMonthBs = substr(DateHelper::getCurrentBS(), 0, 7);

        EmployeeSalaryAdvance::query()->create([
            'employee_id' => $employee->id,
            'party_id' => $employee->party_id,
            'account_id' => $account->id,
            'payment_id' => null,
            'amount' => 9000,
            'advance_date' => DateHelper::bsToAd($salaryMonthBs . '-05'),
            'advance_date_bs' => $salaryMonthBs . '-05',
            'salary_month' => $salaryMonthBs,
            'remarks' => null,
        ]);

        $response = $this->actingAs($user)
            ->from(route('employee-advances.index'))
            ->post(route('employee-advances.store'), [
                'employee_id' => $employee->id,
                'account_id' => $account->id,
                'advance_date_bs' => $salaryMonthBs . '-06',
                'amount' => 2000,
            ]);

        $response
            ->assertRedirect(route('employee-advances.index'))
            ->assertSessionHasErrors(['amount']);

        $this->assertSame(1, EmployeeSalaryAdvance::query()->count());
        $this->assertSame(0, Payment::query()->count());
    }

    private function createEmployeeAndAccount(string $partyName, string $partyPhone, float $salary): array
    {
        $party = Party::query()->create([
            'name' => $partyName,
            'phone' => $partyPhone,
        ]);

        $employee = Employee::query()->create([
            'party_id' => $party->id,
            'salary' => $salary,
        ]);

        $account = Account::query()->create([
            'name' => 'Cash Account',
            'type' => 'cash',
        ]);

        return [$employee, $account];
    }
}
