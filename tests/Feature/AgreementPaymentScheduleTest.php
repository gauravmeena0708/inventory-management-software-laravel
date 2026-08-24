<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Agreement;
use App\Models\Attachment;
use App\Models\FileRecord;
use App\Models\Payment;
use App\Models\User;
use App\Services\Agreements\CompletePaymentAction;
use App\Services\Agreements\GeneratePaymentScheduleAction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Tests\TestCase;

class AgreementPaymentScheduleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test PaymentStatus enum values, labels, colors, and boolean helper methods.
     */
    public function test_payment_status_enum_methods(): void
    {
        $this->assertEquals(['pending', 'completed', 'cancelled'], PaymentStatus::values());
        $this->assertEquals('Pending', PaymentStatus::PENDING->label());
        $this->assertEquals('Completed', PaymentStatus::COMPLETED->label());
        $this->assertEquals('Cancelled', PaymentStatus::CANCELLED->label());

        $this->assertEquals('amber', PaymentStatus::PENDING->color());
        $this->assertEquals('green', PaymentStatus::COMPLETED->color());
        $this->assertEquals('red', PaymentStatus::CANCELLED->color());

        $this->assertTrue(PaymentStatus::PENDING->isPending());
        $this->assertFalse(PaymentStatus::PENDING->isCompleted());
        $this->assertTrue(PaymentStatus::COMPLETED->isCompleted());
        $this->assertTrue(PaymentStatus::CANCELLED->isCancelled());

        $this->assertArrayHasKey('pending', PaymentStatus::labels());
        $this->assertArrayHasKey('completed', PaymentStatus::labels());
        $this->assertArrayHasKey('cancelled', PaymentStatus::labels());
    }

    /**
     * Test Agreement model configuration, casts, relations, and activity logging.
     */
    public function test_agreement_model_configuration_casts_and_relations(): void
    {
        $file = FileRecord::factory()->create(['name' => 'Agreement Contract Doc']);

        $agreement = Agreement::create([
            'name' => 'Data Center AMC 2026',
            'agency' => 'NetServe Systems Pvt Ltd',
            'file_id' => $file->id,
            'type' => 'Infrastructure Maintenance',
            'expiry' => '2026-12-31',
            'annual_cost' => 240000.50,
            'currency' => 'INR',
            'billing_interval_months' => 3,
            'billing_anchor_date' => '2026-01-01',
            'paid_till' => '2026-03-31',
            'remarks' => 'Quarterly billing schedule',
            'legacy_payload' => ['migrated' => true],
        ]);

        $this->assertEquals('2026-12-31', $agreement->expiry->format('Y-m-d'));
        $this->assertEquals('2026-01-01', $agreement->billing_anchor_date->format('Y-m-d'));
        $this->assertEquals('2026-03-31', $agreement->paid_till->format('Y-m-d'));
        $this->assertEquals(240000.50, $agreement->annual_cost);
        $this->assertEquals(3, $agreement->billing_interval_months);
        $this->assertEquals(['migrated' => true], $agreement->legacy_payload);

        $this->assertInstanceOf(BelongsTo::class, $agreement->file());
        $this->assertInstanceOf(HasMany::class, $agreement->payments());
        $this->assertInstanceOf(MorphMany::class, $agreement->attachments());

        $this->assertEquals('Agreement Contract Doc', $agreement->file->name);

        $options = $agreement->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);
    }

    /**
     * Test Payment model configuration, casts, relations, and activity logging.
     */
    public function test_payment_model_configuration_casts_and_relations(): void
    {
        $agreement = Agreement::factory()->create();
        $user = User::factory()->create();

        $payment = Payment::create([
            'agreement_id' => $agreement->id,
            'amount' => 60000.00,
            'currency' => 'INR',
            'due_date' => '2026-04-01',
            'paid_date' => '2026-04-05',
            'status' => PaymentStatus::COMPLETED,
            'invoice_number' => 'INV-2026-0042',
            'remarks' => 'Q1 AMC installment',
            'schedule_key' => sha1("{$agreement->id}-2026-04-01"),
            'completed_by' => $user->id,
        ]);

        $this->assertSame(PaymentStatus::COMPLETED, $payment->status);
        $this->assertEquals('2026-04-01', $payment->due_date->format('Y-m-d'));
        $this->assertEquals('2026-04-05', $payment->paid_date->format('Y-m-d'));
        $this->assertEquals(60000.00, $payment->amount);

        $this->assertInstanceOf(BelongsTo::class, $payment->agreement());
        $this->assertInstanceOf(BelongsTo::class, $payment->completedBy());

        $this->assertEquals($agreement->id, $payment->agreement->id);
        $this->assertEquals($user->id, $payment->completedBy->id);

        $options = $payment->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);
    }

    /**
     * Test Agreement and Payment query scopes.
     */
    public function test_agreement_and_payment_query_scopes(): void
    {
        $expiringSoon = Agreement::factory()->create([
            'expiry' => now()->addDays(30)->toDateString(),
        ]);

        $notExpiringSoon = Agreement::factory()->create([
            'expiry' => now()->addDays(300)->toDateString(),
        ]);

        $expired = Agreement::factory()->create([
            'expiry' => now()->subDays(10)->toDateString(),
        ]);

        // Agreement scopes
        $expiringList = Agreement::expiringSoon(60)->get();
        $this->assertTrue($expiringList->contains('id', $expiringSoon->id));
        $this->assertFalse($expiringList->contains('id', $notExpiringSoon->id));
        $this->assertFalse($expiringList->contains('id', $expired->id));

        $expiredList = Agreement::expired()->get();
        $this->assertTrue($expiredList->contains('id', $expired->id));
        $this->assertFalse($expiredList->contains('id', $expiringSoon->id));

        // Payment scopes
        $pendingPayment = Payment::factory()->pending()->create([
            'due_date' => now()->addDays(15)->toDateString(),
            'schedule_key' => 'scope-test-pending-1',
        ]);

        $overduePayment = Payment::factory()->overdue(10)->create([
            'schedule_key' => 'scope-test-overdue-1',
        ]);

        $completedPayment = Payment::factory()->completed()->create([
            'schedule_key' => 'scope-test-completed-1',
        ]);

        $pendingList = Payment::pending()->get();
        $this->assertTrue($pendingList->contains('id', $pendingPayment->id));
        $this->assertTrue($pendingList->contains('id', $overduePayment->id));
        $this->assertFalse($pendingList->contains('id', $completedPayment->id));

        $overdueList = Payment::overdue()->get();
        $this->assertTrue($overdueList->contains('id', $overduePayment->id));
        $this->assertFalse($overdueList->contains('id', $pendingPayment->id));
        $this->assertFalse($overdueList->contains('id', $completedPayment->id));

        $completedList = Payment::completed()->get();
        $this->assertTrue($completedList->contains('id', $completedPayment->id));
        $this->assertFalse($completedList->contains('id', $pendingPayment->id));
    }

    /**
     * Test quarterly payment schedule generation.
     */
    public function test_generate_quarterly_payment_schedule(): void
    {
        $agreement = Agreement::factory()->create([
            'annual_cost' => 120000.00,
            'currency' => 'INR',
            'billing_interval_months' => 3,
            'billing_anchor_date' => '2026-01-01',
            'expiry' => '2026-12-31',
        ]);

        $action = app(GeneratePaymentScheduleAction::class);
        $payments = $action->execute($agreement);

        $this->assertCount(4, $payments);
        $this->assertEquals(4, $agreement->payments()->count());

        $expectedDueDates = ['2026-01-01', '2026-04-01', '2026-07-01', '2026-10-01'];
        foreach ($payments as $index => $payment) {
            $this->assertEquals($expectedDueDates[$index], $payment->due_date->format('Y-m-d'));
            $this->assertEquals(30000.00, $payment->amount);
            $this->assertEquals('INR', $payment->currency);
            $this->assertSame(PaymentStatus::PENDING, $payment->status);
            $this->assertEquals(sha1("{$agreement->id}-{$expectedDueDates[$index]}"), $payment->schedule_key);
        }
    }

    /**
     * Test monthly payment schedule generation.
     */
    public function test_generate_monthly_payment_schedule(): void
    {
        $agreement = Agreement::factory()->create([
            'annual_cost' => 120000.00,
            'billing_interval_months' => 1,
            'billing_anchor_date' => '2026-01-01',
            'expiry' => '2026-06-30',
        ]);

        $action = app(GeneratePaymentScheduleAction::class);
        $payments = $action->execute($agreement);

        $this->assertCount(6, $payments);
        foreach ($payments as $payment) {
            $this->assertEquals(10000.00, $payment->amount);
            $this->assertSame(PaymentStatus::PENDING, $payment->status);
        }
    }

    /**
     * Test semi-annual payment schedule generation.
     */
    public function test_generate_semi_annual_payment_schedule(): void
    {
        $agreement = Agreement::factory()->create([
            'annual_cost' => 200000.00,
            'billing_interval_months' => 6,
            'billing_anchor_date' => '2026-01-15',
            'expiry' => '2027-01-15',
        ]);

        $action = app(GeneratePaymentScheduleAction::class);
        $payments = $action->execute($agreement);

        $this->assertCount(3, $payments);
        $this->assertEquals('2026-01-15', $payments[0]->due_date->format('Y-m-d'));
        $this->assertEquals('2026-07-15', $payments[1]->due_date->format('Y-m-d'));
        $this->assertEquals('2027-01-15', $payments[2]->due_date->format('Y-m-d'));

        foreach ($payments as $payment) {
            $this->assertEquals(100000.00, $payment->amount);
        }
    }

    /**
     * Test month-end date calculations (e.g. Jan 31 -> Feb 28 -> Mar 31 -> Apr 30).
     */
    public function test_generate_schedule_handles_month_end_anchoring(): void
    {
        $agreement = Agreement::factory()->create([
            'annual_cost' => 120000.00,
            'billing_interval_months' => 1,
            'billing_anchor_date' => '2026-01-31',
            'expiry' => '2026-04-30',
        ]);

        $action = app(GeneratePaymentScheduleAction::class);
        $payments = $action->execute($agreement);

        $this->assertCount(4, $payments);
        $this->assertEquals('2026-01-31', $payments[0]->due_date->format('Y-m-d'));
        $this->assertEquals('2026-02-28', $payments[1]->due_date->format('Y-m-d'));
        $this->assertEquals('2026-03-31', $payments[2]->due_date->format('Y-m-d'));
        $this->assertEquals('2026-04-30', $payments[3]->due_date->format('Y-m-d'));
    }

    /**
     * Test date clamping for 30th of month (e.g. Jan 30 -> Feb 28 -> Mar 30).
     */
    public function test_generate_schedule_handles_day_30_clamping(): void
    {
        $agreement = Agreement::factory()->create([
            'annual_cost' => 60000.00,
            'billing_interval_months' => 1,
            'billing_anchor_date' => '2026-01-30',
            'expiry' => '2026-03-30',
        ]);

        $action = app(GeneratePaymentScheduleAction::class);
        $payments = $action->execute($agreement);

        $this->assertCount(3, $payments);
        $this->assertEquals('2026-01-30', $payments[0]->due_date->format('Y-m-d'));
        $this->assertEquals('2026-02-28', $payments[1]->due_date->format('Y-m-d'));
        $this->assertEquals('2026-03-30', $payments[2]->due_date->format('Y-m-d'));
    }

    /**
     * Test idempotency: re-running schedule generation does not duplicate payments
     * or overwrite completed payments.
     */
    public function test_generate_schedule_is_idempotent(): void
    {
        $user = User::factory()->create();
        $agreement = Agreement::factory()->create([
            'annual_cost' => 120000.00,
            'billing_interval_months' => 3,
            'billing_anchor_date' => '2026-01-01',
            'expiry' => '2026-12-31',
        ]);

        $generateAction = app(GeneratePaymentScheduleAction::class);
        $completeAction = app(CompletePaymentAction::class);

        // 1st run
        $firstRunPayments = $generateAction->execute($agreement);
        $this->assertCount(4, $firstRunPayments);
        $firstPaymentIds = $firstRunPayments->pluck('id')->all();

        // Complete the first payment
        $completedPayment = $completeAction->execute(
            $firstRunPayments[0],
            $user,
            'INV-001',
            Carbon::parse('2026-01-05')
        );

        $this->assertSame(PaymentStatus::COMPLETED, $completedPayment->status);
        $this->assertEquals('INV-001', $completedPayment->invoice_number);

        // 2nd run: should not duplicate or overwrite
        $secondRunPayments = $generateAction->execute($agreement);
        $this->assertCount(4, $secondRunPayments);
        $this->assertEquals($firstPaymentIds, $secondRunPayments->pluck('id')->all());
        $this->assertEquals(4, Payment::where('agreement_id', $agreement->id)->count());

        // The first payment must remain completed with invoice details
        $freshFirstPayment = Payment::find($firstRunPayments[0]->id);
        $this->assertSame(PaymentStatus::COMPLETED, $freshFirstPayment->status);
        $this->assertEquals('INV-001', $freshFirstPayment->invoice_number);
        $this->assertEquals('2026-01-05', $freshFirstPayment->paid_date->format('Y-m-d'));
        $this->assertEquals($user->id, $freshFirstPayment->completed_by);
    }

    /**
     * Test validation exceptions in GeneratePaymentScheduleAction.
     */
    public function test_generate_schedule_validates_inputs(): void
    {
        $action = app(GeneratePaymentScheduleAction::class);

        // Missing anchor date
        $agreement1 = Agreement::factory()->create([
            'billing_anchor_date' => null,
            'expiry' => '2026-12-31',
            'billing_interval_months' => 3,
        ]);
        $this->expectException(InvalidArgumentException::class);
        $action->execute($agreement1);
    }

    /**
     * Test invalid interval validation in GeneratePaymentScheduleAction.
     */
    public function test_generate_schedule_rejects_invalid_interval(): void
    {
        $action = app(GeneratePaymentScheduleAction::class);

        $agreement = Agreement::factory()->create([
            'billing_anchor_date' => '2026-01-01',
            'expiry' => '2026-12-31',
            'billing_interval_months' => 0,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $action->execute($agreement);
    }

    /**
     * Test anchor after expiry validation in GeneratePaymentScheduleAction.
     */
    public function test_generate_schedule_rejects_anchor_after_expiry(): void
    {
        $action = app(GeneratePaymentScheduleAction::class);

        $agreement = Agreement::factory()->create([
            'billing_anchor_date' => '2027-01-01',
            'expiry' => '2026-12-31',
            'billing_interval_months' => 3,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $action->execute($agreement);
    }

    /**
     * Test payment completion workflow and agreement paid_till updates.
     */
    public function test_complete_payment_action_updates_status_and_paid_till(): void
    {
        $user = User::factory()->create();
        $agreement = Agreement::factory()->create([
            'annual_cost' => 120000.00,
            'billing_interval_months' => 3,
            'billing_anchor_date' => '2026-01-01',
            'expiry' => '2026-12-31',
            'paid_till' => null,
        ]);

        $generateAction = app(GeneratePaymentScheduleAction::class);
        $completeAction = app(CompletePaymentAction::class);

        $payments = $generateAction->execute($agreement);
        $p1 = $payments[0]; // due 2026-01-01
        $p2 = $payments[1]; // due 2026-04-01

        // Complete payment 1
        $completeAction->execute($p1, $user, 'INV-2026-001', Carbon::parse('2026-01-02'));

        $freshAgreement = $agreement->fresh();
        $this->assertEquals('2026-01-01', $freshAgreement->paid_till->format('Y-m-d'));

        // Complete payment 2
        $completeAction->execute($p2, $user, 'INV-2026-002', Carbon::parse('2026-04-02'));

        $freshAgreement = $agreement->fresh();
        $this->assertEquals('2026-04-01', $freshAgreement->paid_till->format('Y-m-d'));

        // Completing an earlier payment should not regress paid_till
        $earlierPayment = Payment::factory()->create([
            'agreement_id' => $agreement->id,
            'due_date' => '2025-10-01',
            'schedule_key' => 'early-payment-key-1',
            'status' => PaymentStatus::PENDING,
        ]);

        $completeAction->execute($earlierPayment, $user, 'INV-OLD-01');
        $this->assertEquals('2026-04-01', $agreement->fresh()->paid_till->format('Y-m-d'));
    }

    /**
     * Test complete payment rejects blank invoice number.
     */
    public function test_complete_payment_rejects_blank_invoice(): void
    {
        $user = User::factory()->create();
        $payment = Payment::factory()->pending()->create();

        $action = app(CompletePaymentAction::class);

        $this->expectException(InvalidArgumentException::class);
        $action->execute($payment, $user, '   ');
    }
}
