<x-filament-panels::page>
    @php
        use App\Services\Money;
        $statusColors = [
            'active'    => ['#10b981', '#064e3b'],
            'trialing'  => ['#22d3ee', '#155e75'],
            'past_due'  => ['#f59e0b', '#78350f'],
            'unpaid'    => ['#ef4444', '#7f1d1d'],
            'incomplete'=> ['#f59e0b', '#78350f'],
            'canceled'  => ['#94a3b8', '#334155'],
        ];
        $statusKey   = $subscription?->stripe_status ?: ($isSubscribed ? 'active' : 'none');
        $statusColor = $statusColors[$statusKey] ?? ['#94a3b8', '#334155'];
    @endphp

    @if (session('billing_status'))
        <div style="margin: 0 0 1.5rem; padding: 1rem 1.25rem; border-radius: 0.75rem; background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.35); color: rgb(110,231,183); font-size: 0.9375rem;">
            {{ session('billing_status') }}
        </div>
    @endif
    @if (session('billing_error'))
        <div style="margin: 0 0 1.5rem; padding: 1rem 1.25rem; border-radius: 0.75rem; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.35); color: rgb(252,165,165); font-size: 0.9375rem;">
            {{ session('billing_error') }}
        </div>
    @endif

    {{-- Current state card --}}
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 1rem; padding: 1.75rem; margin: 0 0 2rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: flex-start; justify-content: space-between;">
            <div style="flex: 1; min-width: 280px;">
                <p style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: rgb(148,163,184); margin: 0 0 0.5rem;">
                    {{ __('billing.current_plan') }}
                </p>
                <h2 style="font-size: 1.875rem; font-weight: 700; margin: 0 0 0.25rem;">
                    {{ $currentPlan?->translated('name') ?? __('billing.no_plan') }}
                </h2>
                @if ($currentPlan)
                    <p style="color: rgb(148,163,184); margin: 0 0 1rem; font-size: 0.9375rem;">
                        {{ $currentPlan->translated('tagline') }}
                    </p>
                    <p style="margin: 0; font-size: 0.9375rem;">
                        @if ($currentPlan->isFree())
                            <span style="font-weight: 600;">{{ __('billing.free') }}</span>
                        @else
                            <span style="font-weight: 700; font-size: 1.5rem;">{{ Money::display($currentPlan->effectivePriceCentsFor($currentPeriod), 1, $currentPlan->currency) }}</span>
                            <span style="color: rgb(148,163,184);">/ {{ __('billing.period.' . $currentPeriod) }}</span>
                        @endif
                    </p>
                @endif
            </div>

            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;">
                <span style="display: inline-block; padding: 0.375rem 0.875rem; border-radius: 999px; background: {{ $statusColor[1] }}; color: {{ $statusColor[0] }}; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
                    {{ __('billing.status.' . $statusKey, [], null) ?: $statusKey }}
                </span>
                @if ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
                    <span style="font-size: 0.8125rem; color: rgb(148,163,184);">
                        {{ __('billing.trial_ends', ['date' => $tenant->trial_ends_at->toFormattedDateString()]) }}
                    </span>
                @endif
                @if ($subscription?->ends_at && $subscription->ends_at->isFuture())
                    <span style="font-size: 0.8125rem; color: rgb(252,211,77);">
                        {{ __('billing.cancels_on', ['date' => $subscription->ends_at->toFormattedDateString()]) }}
                    </span>
                @endif
            </div>
        </div>

        @if ($tenant->stripe_id)
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08); display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <form method="post" action="{{ route('billing.portal') }}">
                    @csrf
                    <button type="submit" class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray" style="cursor: pointer;">
                        {{ __('billing.open_portal') }} ↗
                    </button>
                </form>
                <p style="margin: 0; font-size: 0.8125rem; color: rgb(148,163,184); align-self: center;">
                    {{ __('billing.portal_hint') }}
                </p>
            </div>
        @endif
    </div>

    {{-- Plan picker --}}
    <div style="margin: 0 0 1rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.25rem;">{{ __('billing.change_plan') }}</h2>
        <p style="color: rgb(148,163,184); margin: 0; font-size: 0.9375rem;">{{ __('billing.change_plan_hint') }}</p>
    </div>

    {{-- Period toggle. Pure-CSS via :has(:checked). --}}
    <style>
        .billing-period { display: inline-flex; padding: 4px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 999px; margin: 0 0 1.5rem; }
        .billing-period label { padding: 0.5rem 1.25rem; border-radius: 999px; font-size: 0.8125rem; font-weight: 600; cursor: pointer; color: rgb(148,163,184); transition: all .15s ease; }
        .billing-period input { position: absolute; opacity: 0; pointer-events: none; }
        .billing-period:has(input[value="monthly"]:checked) label[data-period="monthly"],
        .billing-period:has(input[value="yearly"]:checked) label[data-period="yearly"] { background: rgb(99,102,241); color: white; }
        .plans-monthly, .plans-yearly { display: none; }
        body:has(input[name="billing_period_picker"][value="monthly"]:checked) .plans-monthly { display: grid; }
        body:has(input[name="billing_period_picker"][value="yearly"]:checked) .plans-yearly { display: grid; }
    </style>

    <div class="billing-period">
        <label data-period="monthly"><input type="radio" name="billing_period_picker" value="monthly" {{ $currentPeriod === 'monthly' ? 'checked' : '' }}> {{ __('billing.period.monthly') }}</label>
        <label data-period="yearly"><input type="radio" name="billing_period_picker" value="yearly" {{ $currentPeriod === 'yearly' ? 'checked' : '' }}> {{ __('billing.period.yearly') }}</label>
    </div>

    @foreach (['monthly', 'yearly'] as $period)
        <div class="plans-{{ $period }}" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
            @foreach ($plans as $plan)
                @php
                    $isCurrent = $plan->slug === $tenant->subscription_plan && $currentPeriod === $period;
                    $cents = $plan->effectivePriceCentsFor($period);
                @endphp
                <div style="background: rgba(255,255,255,0.03); border: 1px solid {{ $isCurrent ? 'rgb(99,102,241)' : 'rgba(255,255,255,0.08)' }}; border-radius: 1rem; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0;">{{ $plan->translated('name') }}</h3>
                            @if ($plan->is_popular)
                                <span style="padding: 0.125rem 0.5rem; background: rgba(99,102,241,0.2); color: rgb(165,180,252); font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; border-radius: 4px;">★</span>
                            @endif
                            @if ($isCurrent)
                                <span style="padding: 0.125rem 0.5rem; background: rgba(16,185,129,0.2); color: rgb(110,231,183); font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; border-radius: 4px;">{{ __('billing.current') }}</span>
                            @endif
                        </div>
                        <p style="margin: 0; font-size: 0.875rem; color: rgb(148,163,184);">{{ $plan->translated('tagline') }}</p>
                    </div>
                    <div>
                        @if ($plan->isFree())
                            <p style="margin: 0; font-size: 1.5rem; font-weight: 700;">{{ __('billing.free') }}</p>
                        @else
                            @if ($plan->hasActiveDiscount())
                                <p style="margin: 0; font-size: 0.8125rem; color: rgb(148,163,184); text-decoration: line-through;">{{ Money::display($plan->priceCentsFor($period), 1, $plan->currency) }}</p>
                            @endif
                            <p style="margin: 0; font-size: 1.75rem; font-weight: 700;">{{ Money::display($cents, 1, $plan->currency) }}</p>
                            <p style="margin: 0; font-size: 0.8125rem; color: rgb(148,163,184);">/ {{ __('billing.period.' . $period) }}</p>
                        @endif
                    </div>
                    <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 0.375rem;">
                        @foreach ((array) $plan->translated('features') as $feature)
                            <li style="font-size: 0.8125rem; color: rgb(203,213,225); display: flex; gap: 0.5rem;"><span style="color: rgb(110,231,183);">✓</span>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    @if ($isCurrent)
                        <button type="button" disabled style="margin-top: auto; padding: 0.75rem; background: rgba(255,255,255,0.06); color: rgb(148,163,184); border: 0; border-radius: 0.5rem; font-weight: 600; cursor: not-allowed;">
                            {{ __('billing.current_plan') }}
                        </button>
                    @elseif (! $plan->isFree() && ! $plan->stripePriceFor($period))
                        <button type="button" disabled title="{{ __('billing.errors.stripe_price_missing') }}" style="margin-top: auto; padding: 0.75rem; background: rgba(255,255,255,0.06); color: rgb(148,163,184); border: 0; border-radius: 0.5rem; font-weight: 600; cursor: not-allowed;">
                            {{ __('billing.unavailable') }}
                        </button>
                    @else
                        <form method="post" action="{{ route('billing.checkout') }}" style="margin-top: auto;">
                            @csrf
                            <input type="hidden" name="plan_slug" value="{{ $plan->slug }}">
                            <input type="hidden" name="period" value="{{ $period }}">
                            <button type="submit" class="fi-btn fi-btn-color-primary fi-btn-size-md fi-color-primary" style="width: 100%; cursor: pointer;">
                                @if ($plan->isFree())
                                    {{ __('billing.switch_to_free') }}
                                @elseif ($tenant->platformSubscribed())
                                    {{ __('billing.switch_plan') }}
                                @else
                                    {{ __('billing.subscribe') }}
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</x-filament-panels::page>
