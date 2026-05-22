<?php

namespace App\Models;

use App\Services\SitePageSchemas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * SuperAdmin-editable content for a marketing page in one locale.
 *
 * The `content` JSON blob holds whatever shape the page-specific schema
 * defines (see {@see SitePageSchemas}). The model knows nothing about
 * which fields exist — it just stores + returns the blob.
 *
 * Reads go through {@see self::text()} which falls back through the
 * resolution chain:
 *   1. DB row for the requested locale
 *   2. DB row for the fallback locale (en)
 *   3. The i18n catalog default declared in SitePageSchemas
 *
 * Reads are cached per (page, locale); writes via Filament bust the cache.
 */
class SitePage extends Model
{
    protected $table = 'site_pages';

    protected $fillable = ['page', 'locale', 'content'];

    protected $casts = [
        'content' => 'array',
    ];

    private const CACHE_KEY_PREFIX = 'site_page:';
    private const CACHE_TTL = 3600;

    /**
     * Resolve the text for a page+field, with locale fallback to en and
     * catalog fallback to i18n. Returns '' if nothing matches (the field
     * isn't in the schema), never null — callers can echo it directly.
     */
    public static function text(string $page, string $field, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $fallbackLocale = (string) config('app.fallback_locale', 'en');

        $value = self::lookupField($page, $locale, $field);
        if ($value !== null && $value !== '') {
            return $value;
        }

        if ($locale !== $fallbackLocale) {
            $value = self::lookupField($page, $fallbackLocale, $field);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        $schema = SitePageSchemas::schemaFor($page);
        $catalogKey = $schema[$field]['fallback'] ?? null;
        if ($catalogKey) {
            return (string) __($catalogKey, [], $locale);
        }
        return '';
    }

    /**
     * Resolve all schema-defined fields for a page+locale at once. Cheaper
     * than calling text() in a loop when the view needs many fields.
     *
     * @return array<string, string>  field key → resolved value
     */
    public static function bulk(string $page, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $out = [];
        foreach (array_keys(SitePageSchemas::schemaFor($page)) as $field) {
            $out[$field] = self::text($page, $field, $locale);
        }
        return $out;
    }

    private static function lookupField(string $page, string $locale, string $field): ?string
    {
        $content = self::loadContent($page, $locale);
        $value = $content[$field] ?? null;
        return is_string($value) ? $value : null;
    }

    /**
     * Cached lookup of the page's content array. We cache the array (a
     * plain PHP value) rather than the Eloquent model itself — storing
     * full Eloquent instances in cache occasionally rehydrates as
     * __PHP_Incomplete_Class when the cache file is read before the
     * class autoloader is warm, which is hard to debug. Arrays sidestep
     * the issue entirely.
     *
     * @return array<string, mixed>  empty when no row exists
     */
    private static function loadContent(string $page, string $locale): array
    {
        return Cache::remember(
            self::cacheKey($page, $locale),
            self::CACHE_TTL,
            function () use ($page, $locale) {
                $row = self::where('page', $page)->where('locale', $locale)->first();
                return $row ? (array) ($row->content ?? []) : [];
            }
        );
    }

    public static function bustCache(string $page, string $locale): void
    {
        Cache::forget(self::cacheKey($page, $locale));
    }

    private static function cacheKey(string $page, string $locale): string
    {
        return self::CACHE_KEY_PREFIX . $page . ':' . $locale;
    }

    /** Convenience for the Filament edit page — load or build the row. */
    public static function forPageLocale(string $page, string $locale): self
    {
        return self::firstOrNew(['page' => $page, 'locale' => $locale]);
    }
}
