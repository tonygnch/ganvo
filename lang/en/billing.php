<?php

return [
    'current_plan' => 'Current plan',
    'no_plan' => 'No plan selected',
    'free' => 'Free',
    'change_plan' => 'Change plan',
    'change_plan_hint' => 'Switch plans any time. Stripe prorates the cost difference automatically.',
    'subscribe' => 'Subscribe',
    'switch_plan' => 'Switch to this plan',
    'switch_to_free' => 'Switch to free',
    'current' => 'Current',
    'unavailable' => 'Not available',
    'open_portal' => 'Manage billing',
    'portal_hint' => 'Update card, view invoices, or cancel anytime through Stripe.',
    'trial_ends' => 'Trial ends :date',
    'cancels_on' => 'Cancels on :date',

    'period' => [
        'monthly' => 'monthly',
        'yearly' => 'yearly',
    ],

    'status' => [
        'active' => 'Active',
        'trialing' => 'Trialing',
        'past_due' => 'Past due',
        'unpaid' => 'Unpaid',
        'incomplete' => 'Incomplete',
        'canceled' => 'Canceled',
        'none' => 'No subscription',
        'switched_to_free' => 'You\'re now on the free plan.',
        'subscription_active' => 'Subscription active — welcome aboard.',
        'plan_swapped' => 'Plan updated. Stripe applied the proration automatically.',
        'already_on_plan' => 'You\'re already on that plan.',
        'scheduled_downgrade_to_free' => 'Your subscription will end at the current period — then you\'ll be on the free plan.',
    ],

    'errors' => [
        'plan_not_found' => 'That plan no longer exists. Try refreshing the page.',
        'stripe_price_missing' => 'This plan isn\'t configured in Stripe yet. Contact support.',
        'stripe_unavailable' => 'Couldn\'t reach Stripe right now. Try again in a moment.',
        'no_customer' => 'You don\'t have an active billing relationship yet. Subscribe to a paid plan first.',
        'no_subscription' => 'You don\'t have an active subscription to change. Subscribe first.',
    ],

    'swap_warning' => 'Changing plans will charge or credit the prorated difference today, and your next bill date stays the same.',
];
