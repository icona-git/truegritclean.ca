<?php

namespace App\Tasks;

use App\Models\SeedKey;
use DNADesign\Elemental\Models\ElementalArea;
use RuntimeException;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

/**
 * Brings a site seeded before the [missing] marker existed (App\Models\MissingContent, Nov 27 2026) up to the current
 * seed: the four About stand-ins (story and bios) and the Privacy effective date.
 *
 *   vendor/bin/sake tasks:seed-placeholders              drafts only
 *   vendor/bin/sake tasks:seed-placeholders --publish    also publishes each record this run changed
 *
 * Each field is changed only while it still holds exactly what the Phase 3 seed wrote, so text anyone has typed is
 * never overwritten ([keep]); a field that already matches the seed is left as it is ([ok]). The new text comes from
 * app/seed/content.yml, the same source a fresh seed uses. Everything is found and checked before anything is
 * written. --publish publishes only the changed record itself (publishSingle), and only when it had no other
 * unpublished edits and was already live; otherwise it stays a draft and the run says so. Nothing is deleted.
 */
class SeedPlaceholdersTask extends BuildTask
{
    // Writes content: CLI only, so no CMS user can run it from /dev/tasks on a live site.
    private static bool $can_run_in_browser = false;

    protected static string $commandName = 'seed-placeholders';

    protected string $title = 'Highlight missing content (About stand-ins, Privacy date)';

    protected static string $description = 'Replace the Phase 3 bracket placeholders on About and Privacy with the '
        . 'highlighted [missing] stand-ins from app/seed/content.yml. Untouched fields only. Drafts only unless --publish.';

    private const CONTENT_FILE = 'app/seed/content.yml';

    /** What the Phase 3 seed wrote: [page key, block key, bucket key, text]. A null bucket means the page's Content. */
    private const BEFORE = [
        ['about', 'about-story', 'who', "<p>[Placeholder: 2–3 sentences from Rebekah. Who you are, how long you've been cleaning, what kind of work you've done.]</p>"],
        ['about', 'about-story', 'why', "<p>[Placeholder: Rebekah's story. Why construction cleaning, and what she saw that could be done better.]</p>"],
        ['about', 'about-team', 'rebekah', '<p>[Placeholder bio, 2 sentences + photo]</p>'],
        ['about', 'about-team', 'miguel', '<p>[Placeholder bio, 2 sentences + photo]</p>'],
        // Privacy: only its first paragraph changes; the rest of the policy is compared and kept as it is.
        ['privacy', null, null, '<p><em>Effective date: [date of launch]</em></p>'],
    ];

    private PolyOutput $output;

    public function getOptions(): array
    {
        return [
            new InputOption('publish', null, InputOption::VALUE_NONE, 'Publish each record this run changed (default: drafts only)'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->output = $output;
        $publish = (bool) $input->getOption('publish');

        $path = BASE_PATH . '/' . self::CONTENT_FILE;
        if (!is_file($path)) {
            $output->writeln("[stop] {$path} not found");
            return 1;
        }
        $content = Yaml::parseFile($path);

        return Versioned::withVersionedMode(function () use ($content, $publish): int {
            Versioned::set_stage(Versioned::DRAFT);

            try {
                $plan = $this->check($content);
            } catch (RuntimeException $e) {
                $this->output->writeln('[stop] ' . $e->getMessage() . ' Nothing was written.');
                return 1;
            }

            $changed = 0;
            $published = 0;
            foreach ($plan as [$label, $record, $field, $before, $after]) {
                $current = (string) $record->$field;
                if (str_contains($current, $after)) {
                    $this->output->writeln("  [ok] {$label}: already the current seed");
                    continue;
                }
                if (!str_contains($current, $before) || substr_count($current, $before) !== 1) {
                    $this->output->writeln("  [keep] {$label}: someone has edited it; left alone");
                    continue;
                }
                // Read before the write: a record with other unpublished edits must not have them published by us.
                $hadDraftEdits = $record->isModifiedOnDraft();
                $wasLive = $record->isPublished();

                $record->$field = str_replace($before, $after, $current);
                $record->write();
                $changed++;

                if (!$publish) {
                    $this->output->writeln("  [set] {$label}: draft");
                } elseif ($hadDraftEdits || !$wasLive) {
                    $this->output->writeln(sprintf(
                        '  [set] %s: draft only, %s; publish it in the CMS when ready',
                        $label,
                        $hadDraftEdits ? 'it had other unpublished edits' : 'it was not published before'
                    ));
                } else {
                    $record->publishSingle();
                    $published++;
                    $this->output->writeln("  [set] {$label}: published");
                }
            }

            $this->output->writeln(sprintf(
                'Done: %d changed (%d published), %d left as they were.',
                $changed,
                $published,
                count($plan) - $changed
            ));
            return 0;
        });
    }

    /** @return array<int, array{0: string, 1: DataObject, 2: string, 3: string, 4: string}> */
    private function check(array $content): array
    {
        $defs = [];
        foreach ($content['pages'] ?? [] as $def) {
            $defs[$def['key']] = $def;
        }

        $plan = [];
        foreach (self::BEFORE as [$pageKey, $blockKey, $bucketKey, $before]) {
            $label = $pageKey . ($blockKey ? "/{$blockKey}/{$bucketKey}" : ' Content');
            $def = $defs[$pageKey] ?? throw new RuntimeException("{$label}: page key not in " . self::CONTENT_FILE . '.');

            $page = SiteTree::get()->filter(['URLSegment' => $def['URLSegment'], 'ParentID' => 0])->first()
                ?? throw new RuntimeException("{$label}: no page at /{$def['URLSegment']}/ in Draft.");

            if ($blockKey === null) {
                // The seed's first paragraph of Content is the new effective-date line.
                $after = trim(explode("\n", (string) ($def['Content'] ?? ''))[0]);
                if (!str_starts_with($after, '<p><em>Effective date: [missing')) {
                    throw new RuntimeException("{$label}: the seed's first paragraph is not the [missing] effective date.");
                }
                $plan[] = [$label, $page, 'Content', $before, $after];
                continue;
            }

            $area = $page->ElementalArea();
            if (!$area instanceof ElementalArea || !$area->exists()) {
                throw new RuntimeException("{$label}: the page has no block area.");
            }
            $block = SeedKey::findBlock($area, $blockKey) ?? throw new RuntimeException("{$label}: no block with that seed key.");
            $bucket = SeedKey::findBucket($block, $bucketKey) ?? throw new RuntimeException("{$label}: no item with that seed key.");

            $after = null;
            foreach ($def['blocks'] ?? [] as $blockDef) {
                if (($blockDef['key'] ?? null) !== $blockKey) {
                    continue;
                }
                foreach ($blockDef['buckets'] ?? [] as $bucketDef) {
                    if (($bucketDef['key'] ?? null) === $bucketKey) {
                        $after = trim((string) ($bucketDef['HTML'] ?? ''));
                    }
                }
            }
            if ($after === null || !str_contains($after, '[missing')) {
                throw new RuntimeException("{$label}: the seed's text for this item has no [missing] marker.");
            }
            $plan[] = [$label, $bucket, 'HTML', $before, $after];
        }
        return $plan;
    }
}
