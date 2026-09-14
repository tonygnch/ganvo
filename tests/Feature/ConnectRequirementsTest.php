<?php

namespace Tests\Feature;

use App\Filament\StoreAdmin\Pages\Payments;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\ConnectRequirements;
use App\Services\Payments\StripeConnectService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Stripe\Account;
use Tests\TestCase;

/**
 * A restricted Stripe account, explained and fixable from the Payments page.
 *
 * A test shop filled in Stripe's onboarding with realistic details, every
 * identity field failed verification, and the page showed "Причина:
 * requirements.past_due" with Dashboard / Refresh / Disconnect — no way back
 * into the form that would have fixed it. These pin the three parts of the fix:
 * what Stripe is waiting for is stored on every sync, it reads as words, and a
 * restriction an owner can clear gets "Continue setup" while a rejection does not.
 *
 * Stripe is never called: accounts are built locally and the page's sync is mocked.
 */
class ConnectRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function restrictedAccount(string $reason = 'requirements.past_due'): Account
    {
        return Account::constructFrom([
            'id' => 'acct_requirements_test',
            'object' => 'account',
            'charges_enabled' => false,
            'payouts_enabled' => false,
            'details_submitted' => true,
            'requirements' => [
                'disabled_reason' => $reason,
                'past_due' => ['individual.dob.day', 'individual.first_name', 'individual.address.line1', 'individual.phone'],
                'currently_due' => ['individual.dob.day', 'external_account'],
                'errors' => [
                    ['requirement' => 'individual.dob.day', 'code' => 'verification_failed_keyed_identity', 'reason' => 'The identity could not be verified.'],
                ],
            ],
        ]);
    }

    private function shop(): User
    {
        $tenant = Tenant::create([
            'name' => 'Payments Test',
            'slug' => 'payments-test',
            'status' => Tenant::STATUS_ACTIVE,
            'onboarding_step' => 'done',
            'stripe_account_id' => 'acct_requirements_test',
            'stripe_connect_account_type' => 'express',
        ]);

        Store::create(['tenant_id' => $tenant->id, 'theme' => 'wick', 'currency' => 'EUR', 'is_live' => true]);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Payments Owner',
            'email' => 'owner@payments-test.test',
            'password' => bcrypt(str()->random(32)),
        ]);
    }

    private function openPaymentsPage(User $owner)
    {
        $this->actingAs($owner);
        Filament::setCurrentPanel('store');
        $this->mock(StripeConnectService::class, fn ($mock) => $mock->shouldReceive('syncFromStripe')->andReturnNull());

        return Livewire::test(Payments::class);
    }

    public function test_a_sync_keeps_what_stripe_is_waiting_for(): void
    {
        $tenant = $this->shop()->tenant;

        (new StripeConnectService('sk_test_never_called'))->mirrorAccount($tenant, $this->restrictedAccount());

        $tenant->refresh();
        $this->assertSame('requirements.past_due', $tenant->stripe_connect_disabled_reason);
        $this->assertSame(
            ['individual.dob.day', 'individual.first_name', 'individual.address.line1', 'individual.phone', 'external_account'],
            $tenant->stripe_connect_requirements['due'],
        );
        $this->assertSame(['individual.dob.day'], $tenant->stripe_connect_requirements['failed']);
    }

    public function test_requirement_paths_read_as_words(): void
    {
        $this->assertSame(
            [
                __('admin.payments.requirement.dob'),
                __('admin.payments.requirement.name'),
                __('admin.payments.requirement.address'),
                __('admin.payments.requirement.phone'),
                __('admin.payments.requirement.bank_account'),
            ],
            ConnectRequirements::labels([
                'individual.dob.day', 'individual.dob.month', 'individual.first_name', 'individual.last_name',
                'individual.address.line1', 'individual.phone', 'external_account',
            ]),
        );

        $this->assertSame('id_document', ConnectRequirements::groupOf('person_1AbC.verification.document'));
        $this->assertSame('other', ConnectRequirements::groupOf('something_stripe_adds_later'));
        $this->assertSame(__('admin.payments.text.reason_fallback'), ConnectRequirements::reason('a_code_stripe_adds_later'));
    }

    public function test_a_restriction_the_owner_can_clear_offers_continue_setup(): void
    {
        $owner = $this->shop();
        (new StripeConnectService('sk_test_never_called'))->mirrorAccount($owner->tenant, $this->restrictedAccount());

        $this->openPaymentsPage($owner)
            ->assertSee(__('admin.payments.action.continue_setup'))
            ->assertSee(__('admin.payments.reason.requirements_past_due'))
            ->assertSee(__('admin.payments.requirement.dob'))
            ->assertSee(__('admin.payments.requirement.bank_account'))
            ->assertDontSee('requirements.past_due');
    }

    public function test_a_rejected_account_is_not_sent_back_into_the_form(): void
    {
        $owner = $this->shop();
        (new StripeConnectService('sk_test_never_called'))->mirrorAccount($owner->tenant, $this->restrictedAccount('rejected.fraud'));

        $this->openPaymentsPage($owner)
            ->assertSee(__('admin.payments.reason.rejected'))
            ->assertDontSee(__('admin.payments.action.continue_setup'));
    }
}
