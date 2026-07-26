<?php

namespace App\Support;

use App\Models\User;

/**
 * The languages the ADMIN PANELS offer, deliberately kept apart from
 * App\Http\Middleware\SetLocale::SUPPORTED, which governs the storefronts.
 *
 * They are separate because they answer to different people. A storefront's
 * language is a decision about that shop's customers — the owner asked for
 * Bulgarian only there, and adding English back to SetLocale would put a
 * language switcher in front of every shopper on every storefront. The panel's
 * language is a decision about ONE merchant looking at their own workspace, and
 * costs no one else anything.
 *
 * Both lists happen to live in the same lang/{bg,en}/ files; only who gets to
 * choose between them differs.
 */
class PanelLocale
{
    /** @var array<string, string> code => the language's name IN that language */
    public const SUPPORTED = [
        'bg' => 'Български',
        'en' => 'English',
    ];

    public const DEFAULT = 'bg';

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, self::SUPPORTED);
    }

    /**
     * What this user should see. Their saved choice wins; failing that the
     * server default, and failing THAT our own default — so a deployment with
     * an APP_LOCALE the panel has no translations for still lands somewhere
     * readable instead of on raw keys.
     */
    public static function forUser(?User $user): string
    {
        if (self::isSupported($user?->locale)) {
            return $user->locale;
        }

        $app = config('app.locale');

        return self::isSupported($app) ? $app : self::DEFAULT;
    }
}
