<?php

namespace Tests\Feature;

use App\Helpers\DateHelper;
use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeSalaryAdvance;
use App\Models\EmployeeSalary;
use App\Models\Party;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSalaryPaymentKindTest extends TestCase
{
    use RefreshDatabase;

    public function test_salary_sheet_posts_payable_expense_payment_on_month_end(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        [$employee, $account] = $this->createEmployeeAndAccount('Salary Employee', '9800000701', 15000);
        $salaryMonthBs = substr(DateHelper::getCurrentBS(), 0, 7);
        [$bsYear, $bsMonth] = array_map('intval', explode('-', $salaryMonthBs));
        $monthEndBs = sprintf('%s-%02d', $salaryMonthBs, DateHelper::getDaysInMonth($bsYear, $bsMonth));

        $this->actingAs($user)
            ->post(route('employee-salaries.store'), [
                'salary_month_bs' => $salaryMonthBs,
                'salary_date_bs' => $monthEndBs,
                'account_id' => $account->id,
                'save_as_expense' => '1',
                'leaves' => [$employee->id => 0],
                'overtimes' => [$employee->id => 0],
            ])
            ->assertRedirect();

        $salary = EmployeeSalary::query()->firstOrFail();

        $this->assertNotNull($salary->expense_payment_id);

        $payment = Payment::query()->findOrFail($salary->expense_payment_id);

        $this->assertSame('payable', $payment->payment_kind);
        $this->assertSame('given', $payment->type);
        $this->assertSame($employee->party_id, $payment->party_id);
    }

    public function test_salary_sheet_rejects_non_month_end_salary_date(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        [$employee, $account] = $this->createEmployeeAndAccount('Month End Employee', '9800000702', 12000);
        $salaryMonthBs = substr(DateHelper::getCurrentBS(), 0, 7);

        $response = $this->actingAs($user)
            ->from(route('employee-salaries.create'))
            ->post(route('employee-salaries.store'), [
                'salary_month_bs' => $salaryMonthBs,
                'salary_date_bs' => $salaryMonthBs . '-01',
                'account_id' => $account->id,
                'save_as_expense' => '1',
                'leaves' => [$employee->id => 0],
                'overtimes' => [$employee->id => 0],
            ]);

        $response
            ->assertRedirect(route('employee-salaries.create'))
            ->assertSessionHasErrors(['salary_date_bs']);

        $this->assertSame(0, EmployeeSalary::query()->count());
    }

    public function test_salary_sheet_deducts_same_month_employee_advance_from_net_salary(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        [$employee, $account] = $this->createEmployeeAndAccount('Advance Deduct Employee', '9800000703', 12000);
        $salaryMonthBs = substr(DateHelper::getCurrentBS(), 0, 7);
        [$bsYear, $bsMonth] = array_map('intval', explode('-', $salaryMonthBs));
        $monthEndBs = sprintf('%s-%02d', $salaryMonthBs, DateHelper::getDaysInMonth($bsYear, $bsMonth));

        EmployeeSalaryAdvance::query()->create([
            'employee_id' => $employee->id,
            'party_id' => $employee->party_id,
            'account_id' => $account->id,
            'payment_id' => null,
            'amount' => 3000,
            'advance_date' => DateHelper::bsToAd($salaryMonthBs . '-05'),
            'advance_date_bs' => $salaryMonthBs . '-05',
            'salary_month' => $salaryMonthBs,
            'remarks' => 'Test advance',
        ]);

        $this->actingAs($user)
            ->post(route('employee-salaries.store'), [
                'salary_month_bs' => $salaryMonthBs,
                'salary_date_bs' => $monthEndBs,
                'account_id' => $account->id,
                'save_as_expense' => '1',
                'leaves' => [$employee->id => 0],
                'overtimes' => [$employee->id => 0],
            ])
            ->assertRedirect();

        $salary = EmployeeSalary::query()->where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame('3000.00', (string) $salary->advance_deduction_amount);
        $this->assertSame('3000.00', (string) $salary->deduction);
        $this->assertSame('9000.00', (string) $salary->net_salary);

        $payment = Payment::query()->findOrFail($salary->expense_payment_id);

        $this->assertSame('payable', $payment->payment_kind);
        $this->assertSame('given', $payment->type);
        $this->assertSame('9000.00', $payment->amount);
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
