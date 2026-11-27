<?php

namespace App\Models;

use SilverStripe\Control\Director;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Marks content the client has not sent yet, so nobody mistakes a stand-in for the real thing.
 *
 * Text: wrap the stand-in in any HTML field, inside one paragraph:
 *
 *   <p>[missing note="Rebekah to replace: a 2-sentence bio."]Rebekah co-founded TrueGrit Clean Co.[/missing]</p>
 *   <p>Effective date: [missing note="Set at launch."]date of launch[/missing]</p>
 *   <p>[missing note="A photo caption."][/missing]                      (label only, no stand-in text)
 *
 * It renders as a black "Content needed" label (with the note) followed by the stand-in text on a yellow highlight
 * (css/src/components/_missing-content.scss), in every environment: a stand-in must never pass as real copy, so
 * production shows the highlight too. site/LAUNCH.md counts "content-missing" on every page before launch; the
 * count must be 0. To finish an item, an editor replaces the whole [missing]...[/missing] with the real text.
 *
 * Photos: templates test $ShowMissingPhotos (true on dev and test, false on live) to draw a "Photo needed" box where
 * a photo slot is empty. A missing photo is a designed state at launch (the team card shows text only), so live
 * keeps that and the box is a review aid for the local site and staging.
 */
final class MissingContent implements TemplateGlobalProvider
{
    /** Registered in app/_config.php as [missing]. */
    public static function shortcode($arguments, $content = null, $parser = null, $tagName = null): string
    {
        // TinyMCE may store the note's quotes as entities; decode once, then escape for output.
        $note = trim(html_entity_decode((string) ($arguments['note'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = $content === null ? '' : trim($parser instanceof ShortcodeParser ? $parser->parse($content) : (string) $content);

        $label = '<span class="content-missing__flag">'
            . self::escape(_t(self::class . '.CONTENT_NEEDED', 'Content needed'))
            . '</span>';
        if ($note !== '') {
            $label .= ' <span class="content-missing__note">' . self::escape($note) . '</span>';
        }

        return '<span class="content-missing">'
            . '<span class="content-missing__label">' . $label . '</span>'
            . ($text !== '' ? ' <span class="content-missing__text">' . $text . '</span>' : '')
            . '</span>';
    }

    public static function get_template_global_variables(): array
    {
        return ['ShowMissingPhotos' => 'showMissingPhotos'];
    }

    public static function showMissingPhotos(): bool
    {
        return !Director::isLive();
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
