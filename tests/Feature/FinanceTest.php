<?php

namespace Tests\Feature;

use App\Models\CourseSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Trainee;
use App\Models\Trainer;
use App\Models\TrainerPayment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_finance(): void
    {
        $this->get(route('invoices.index'))->assertRedirect(route('login'));
        $this->get(route('trainer-payments.index'))->assertRedirect(route('login'));
    }

    public function test_finance_permissions_are_enforced(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('invoices.index'))->assertForbidden();
        $this->actingAs($user)->get(route('trainer-payments.index'))->assertForbidden();

        $this->actingAs($this->actor(['PAYMENTS_MANAGE']))
            ->get(route('invoices.index'))
            ->assertOk();

        $this->actingAs($this->actor(['PAYMENTS_MANAGE']))
            ->get(route('invoices.create'))
            ->assertForbidden();

        $this->actingAs($this->actor(['INVOICES_MANAGE']))
            ->get(route('trainer-payments.index'))
            ->assertForbidden();
    }

    public function test_invoice_is_created_with_calculated_total(): void
    {
        $session = CourseSession::factory()->create();
        $trainee = Trainee::factory()->create(['full_name' => 'متدرب فاتورة']);

        $this->actingAs($this->actor(['INVOICES_MANAGE']))
            ->post(route('invoices.store'), [
                'session_id' => $session->id,
                'bill_to' => 'trainee',
                'trainee_id' => $trainee->id,
                'quantity' => 2,
                'amount' => 100,
                'tax_amount' => 14,
                'status' => 'issued',
                'issue_date' => '2026-03-01',
                'due_date' => '2026-03-15',
            ])
            ->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('214.00', $invoice->total_amount);
        $this->assertSame('issued', $invoice->status->value);
        $this->assertSame($trainee->id, $invoice->trainee_id);
        $this->assertNull($invoice->provider_id);
        $this->assertNotEmpty($invoice->invoice_no);
        $this->assertStringStartsWith('INV-', $invoice->invoice_no);
    }

    public function test_payments_update_invoice_status_from_partial_to_paid(): void
    {
        $invoice = $this->invoice(200);
        $actor = $this->actor(['PAYMENTS_MANAGE']);

        $this->actingAs($actor)
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => 50,
                'method' => 'cash',
                'paid_at' => '2026-03-02 10:00:00',
            ])
            ->assertRedirect(route('invoices.show', $invoice));

        $this->assertSame('partially_paid', $invoice->refresh()->status->value);
        $this->assertSame(50.0, (float) $invoice->payments()->sum('amount'));

        $this->actingAs($actor)
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => 150,
                'method' => 'bank_transfer',
            ])
            ->assertRedirect(route('invoices.show', $invoice));

        $this->assertSame('paid', $invoice->refresh()->status->value);
        $this->assertSame(2, Payment::count());
    }

    public function test_payment_cannot_exceed_invoice_remaining_amount(): void
    {
        $invoice = $this->invoice(100);

        $this->actingAs($this->actor(['PAYMENTS_MANAGE']))
            ->post(route('invoices.payments.store', $invoice), ['amount' => 150])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('issued', $invoice->refresh()->status->value);
    }

    public function test_invoice_with_payments_cannot_be_deleted(): void
    {
        $invoice = $this->invoice(100);
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 20,
            'paid_at' => now(),
        ]);

        $this->actingAs($this->actor(['INVOICES_MANAGE']))
            ->from(route('invoices.show', $invoice))
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_trainer_payment_amount_is_calculated_and_status_transitions(): void
    {
        $trainer = Trainer::factory()->create();
        $session = CourseSession::factory()->create();
        $actor = $this->actor(['TRAINER_PAYMENTS_MANAGE']);

        $this->actingAs($actor)
            ->post(route('trainer-payments.store'), [
                'trainer_id' => $trainer->id,
                'session_id' => $session->id,
                'total_hours' => 6,
                'hourly_rate' => 150,
            ])
            ->assertRedirect(route('trainer-payments.index'));

        $payment = TrainerPayment::firstOrFail();
        $this->assertSame('900.00', $payment->amount);
        $this->assertSame('pending', $payment->status->value);

        $this->actingAs($actor)
            ->patch(route('trainer-payments.status', $payment), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertSame('approved', $payment->refresh()->status->value);
        $this->assertSame($actor->id, $payment->approved_by);
        $this->assertNull($payment->paid_at);

        $this->actingAs($actor)
            ->patch(route('trainer-payments.status', $payment), ['status' => 'paid'])
            ->assertRedirect();

        $this->assertSame('paid', $payment->refresh()->status->value);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_trainer_payment_cannot_be_duplicated_for_the_same_session(): void
    {
        $trainer = Trainer::factory()->create();
        $session = CourseSession::factory()->create();
        $actor = $this->actor(['TRAINER_PAYMENTS_MANAGE']);

        $payload = [
            'trainer_id' => $trainer->id,
            'session_id' => $session->id,
            'total_hours' => 2,
            'hourly_rate' => 100,
        ];

        $this->actingAs($actor)->post(route('trainer-payments.store'), $payload)->assertRedirect();
        $this->actingAs($actor)->post(route('trainer-payments.store'), $payload)->assertSessionHasErrors('session_id');

        $this->assertDatabaseCount('trainer_payments', 1);
    }

    private function invoice(float $total): Invoice
    {
        $session = CourseSession::factory()->create();
        $trainee = Trainee::factory()->create();

        return Invoice::create([
            'invoice_no' => 'INV-TEST-'.fake()->unique()->numerify('####'),
            'session_id' => $session->id,
            'bill_to' => 'trainee',
            'trainee_id' => $trainee->id,
            'quantity' => 1,
            'amount' => $total,
            'tax_amount' => 0,
            'total_amount' => $total,
            'status' => 'issued',
            'issue_date' => '2026-03-01',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }
}
