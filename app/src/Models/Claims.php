<?php

namespace App\Models;

use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Claims that must not appear on the site until the client confirms they are true (BUILD-HANDOFF "Claims
 * rule"). Each claim is a Settings > Claims checkbox, default off (App\Extensions\MySiteConfig).
 *
 * Three ways content waits for a claim:
 *  - a bucket (checklist item, FAQ) ticks claims in its "Show only when confirmed" field
 *    (App\Models\Bucket::IsShown(); templates loop BucketsBlock::ShownBuckets());
 *  - a sentence inside any HTML field is wrapped in [claim key=minimum]...[/claim] (shortcode());
 *  - a template tests the checkbox itself, e.g. $SiteConfig.ClaimInsured for a trust badge.
 *
 * Everything fails closed: an unknown key or a missing key counts as not confirmed, so a typo hides the item
 * instead of publishing it.
 */
final class Claims
{
    /** Key (used in buckets and the shortcode) => [SiteConfig field, label in the CMS]. */
    public const CLAIMS = [
        'insured' => ['ClaimInsured', 'Insured (certificate available on request)'],
        'worksafebc' => ['ClaimWorkSafeBC', 'WorkSafeBC registered (clearance letter on request)'],
        'hepa' => ['ClaimHEPA', 'HEPA equipment'],
        'checklist' => ['ClaimSignedChecklist', 'Quality checklist signed off at handover, on every job'],
        'photos' => ['ClaimJobPhotos', 'Before-and-after or completion photos on every job'],
        'minimum' => ['ClaimMinimumPrice', 'Minimum job price ($475 in the seeded copy)'],
    ];

    /** Key => CMS label, for checkbox lists. */
    public static function options(): array
    {
        return array_map(fn (array $claim) => $claim[1], self::CLAIMS);
    }

    public static function confirmed(string $key): bool
    {
        $field = self::CLAIMS[$key][0] ?? null;
        if ($field === null) {
            return false;
        }
        return (bool) SiteConfig::current_site_config()->getField($field);
    }

    /** True when every key is confirmed; an empty list is always shown. */
    public static function allConfirmed(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!self::confirmed((string) $key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * The keys stored in a Claims field: CheckboxSetField saves a JSON array; a hand-typed comma list also
     * works. Blank entries are dropped, unknown keys are kept (and fail closed in confirmed()).
     */
    public static function decode(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }
        $keys = json_decode($value, true);
        if (!is_array($keys)) {
            $keys = explode(',', $value);
        }
        return array_values(array_filter(array_map(fn ($key) => trim((string) $key), $keys), 'strlen'));
    }

    /**
     * The keys in a shortcode's key attribute: one key, or several joined with "+" (all must be confirmed).
     * The shortcode parser stops a bare value at a comma or space, so "+" is the separator.
     */
    public static function shortcodeKeys(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode('+', $value)), 'strlen'));
    }

    /**
     * [claim key=minimum]Jobs start at a $475 minimum.[/claim]: the wrapped text renders only while that claim
     * is ticked in Settings > Claims. key=checklist+photos waits for both. Registered in app/_config.php.
     */
    public static function shortcode($arguments, $content = null, $parser = null, $tagName = null): string
    {
        $keys = self::shortcodeKeys((string) ($arguments['key'] ?? ''));
        if (!$keys || !self::allConfirmed($keys) || $content === null) {
            return '';
        }
        return $parser instanceof ShortcodeParser ? $parser->parse($content) : (string) $content;
    }
}
