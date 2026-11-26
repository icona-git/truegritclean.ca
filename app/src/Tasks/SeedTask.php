<?php

namespace App\Tasks;

use App\Models\BlockStyle;
use App\Models\Bucket;
use App\Models\Claims;
use App\Models\SeedKey;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use RuntimeException;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\ORM\DataObject;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\UserForms\Model\UserDefinedForm;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds the launch pages, their blocks and the Settings text from app/seed/content.yml, which carries the
 * copy of content/TrueGrit-Website-Content.md (the project docs folder above this repo).
 *
 *   vendor/bin/sake tasks:seed              drafts only, skips every page that already exists
 *   vendor/bin/sake tasks:seed --publish    also publishes the pages it wrote
 *   vendor/bin/sake tasks:seed --force      also overwrites pages that exist (seeded fields and seeded blocks)
 *
 * Safe by default: a page that exists is left alone, so a re-run never overwrites the client's edits, and a
 * Settings field that already holds text is left alone too. --force overwrites both; never use it on a site
 * whose content the client has started editing. Settings > Claims is never touched: every claim stays off
 * until someone ticks it by hand.
 *
 * Starter rules (README "Seeding content"): runs in the Draft stage; finds its blocks and buckets by
 * SeedKey, never by Title; leaves Sort 1 of a form to userforms (createInitialFormStep()). Blocks and buckets
 * an editor added have no SeedKey and are never touched. Nothing is deleted.
 *
 * The whole file is checked before anything is written: unknown page types, fields, block styles, claim keys
 * and link targets stop the task with nothing changed.
 */
class SeedTask extends BuildTask
{
    // Writes content: CLI only, so no CMS user can run it from /dev/tasks on a live site.
    private static bool $can_run_in_browser = false;

    protected static string $commandName = 'seed';

    protected string $title = 'Seed site content';

    protected static string $description = 'Build the launch pages, blocks and Settings text from app/seed/content.yml. '
        . 'Skips pages that exist unless --force. Drafts only unless --publish.';

    private const CONTENT_FILE = 'app/seed/content.yml';

    private const PAGE_KEYS = ['key', 'class', 'parent', 'blocks'];

    private const BLOCK_KEYS = ['key', 'class', 'style', 'cta', 'buckets'];

    private const BUCKET_KEYS = ['key', 'claims', 'link'];

    private PolyOutput $output;

    /** Page key => page record (existing or seeded), for parents and links. */
    private array $pages = [];

    public function getOptions(): array
    {
        return [
            new InputOption('publish', null, InputOption::VALUE_NONE, 'Publish every page this run wrote (default: drafts only)'),
            new InputOption('force', null, InputOption::VALUE_NONE, 'Overwrite pages and Settings text that already exist. Never on a site the client edits.'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->output = $output;
        $force = (bool) $input->getOption('force');
        $publish = (bool) $input->getOption('publish');

        $path = BASE_PATH . '/' . self::CONTENT_FILE;
        if (!is_file($path)) {
            $output->writeln("[stop] {$path} not found");
            return 1;
        }
        $content = Yaml::parseFile($path);

        return Versioned::withVersionedMode(function () use ($content, $force, $publish): int {
            // Elemental creates a page's block area only in Draft; a Live-stage seed writes blocks to area 0.
            Versioned::set_stage(Versioned::DRAFT);

            try {
                $this->check($content);
            } catch (RuntimeException $e) {
                $this->output->writeln('[stop] ' . $e->getMessage() . ' Nothing was written.');
                return 1;
            }

            $this->output->writeln('--- Settings ---');
            $this->seedSiteConfig($content['siteconfig'] ?? [], $force);

            $this->output->writeln('--- Pages ---');
            $written = [];
            foreach ($content['pages'] as $def) {
                $page = $this->seedPage($def, $force);
                if ($page) {
                    $written[$def['key']] = [$page, $def];
                }
            }

            // Blocks second: their links point at pages, which all exist by now.
            foreach ($written as $key => [$page, $def]) {
                $this->output->writeln("--- Blocks: {$key} ---");
                $this->seedBlocks($page, $def['blocks'] ?? []);
                if ($page instanceof UserDefinedForm) {
                    // userforms owns Sort 1: the form's first record is its initial step (Phase 5 adds fields from Sort 2).
                    $page->createInitialFormStep(true);
                }
            }

            if ($publish && $written) {
                $this->output->writeln('--- Publishing ---');
                foreach ($written as $key => [$page]) {
                    $page->publishRecursive();
                    $this->output->writeln("  [published] {$key}");
                }
            }

            $this->output->writeln(sprintf(
                'Done: %d page(s) written%s, %d skipped.',
                count($written),
                $publish ? ' and published' : ' as drafts',
                count($content['pages']) - count($written)
            ));
            return 0;
        });
    }

    // ─── Checks (before any write) ───────────────────────────────────────────

    private function check(array $content): void
    {
        foreach (array_keys($content['siteconfig'] ?? []) as $field) {
            if (str_starts_with((string) $field, 'Claim')) {
                throw new RuntimeException("Settings field {$field}: claims are ticked by hand, never seeded.");
            }
            $this->checkField(SiteConfig::class, (string) $field, 'Settings');
        }

        $keys = [];
        foreach ($content['pages'] ?? [] as $def) {
            $key = $def['key'] ?? '';
            if ($key === '' || isset($keys[$key])) {
                throw new RuntimeException("Page key \"{$key}\" is empty or used twice.");
            }
            $keys[$key] = true;
        }

        foreach ($content['pages'] ?? [] as $def) {
            $where = "Page {$def['key']}";
            $this->checkClass($def['class'] ?? '', SiteTree::class, $where);
            if (!empty($def['parent']) && !isset($keys[$def['parent']])) {
                throw new RuntimeException("{$where}: parent \"{$def['parent']}\" is not a page in the file.");
            }
            $this->checkFields($def, self::PAGE_KEYS, $def['class'], $where);

            foreach ($def['blocks'] ?? [] as $block) {
                $at = "{$where}, block " . ($block['key'] ?? '?');
                $this->checkClass($block['class'] ?? '', BaseElement::class, $at);
                if (!empty($block['style'])) {
                    $this->styleId($block['class'], $block['style'], $at);
                }
                $this->checkFields($block, self::BLOCK_KEYS, $block['class'], $at);
                $this->checkLink($block['cta'] ?? null, $keys, $at);

                foreach ($block['buckets'] ?? [] as $bucket) {
                    $in = "{$at}, bucket " . ($bucket['key'] ?? '?');
                    $this->checkFields($bucket, self::BUCKET_KEYS, Bucket::class, $in);
                    $this->checkLink($bucket['link'] ?? null, $keys, $in);
                    foreach ($bucket['claims'] ?? [] as $claim) {
                        if (!isset(Claims::CLAIMS[$claim])) {
                            throw new RuntimeException("{$in}: unknown claim \"{$claim}\".");
                        }
                    }
                }
            }
        }
    }

    private function checkClass(string $class, string $base, string $where): void
    {
        if (!class_exists($class) || !is_a($class, $base, true)) {
            throw new RuntimeException("{$where}: \"{$class}\" is not a {$base}.");
        }
    }

    private function checkFields(array $def, array $reserved, string $class, string $where): void
    {
        foreach ($def as $field => $value) {
            if (in_array($field, $reserved, true)) {
                continue;
            }
            $this->checkField($class, (string) $field, $where);
            if (is_string($value)) {
                $this->checkClaimShortcodes($value, "{$where}, {$field}");
            }
        }
    }

    private function checkField(string $class, string $field, string $where): void
    {
        if (!DataObject::singleton($class)->hasDatabaseField($field)) {
            throw new RuntimeException("{$where}: {$class} has no field \"{$field}\".");
        }
    }

    /** Every [claim key=...] in the copy must name known claims, or the sentence would hide for good. */
    private function checkClaimShortcodes(string $html, string $where): void
    {
        preg_match_all('/\[claim\b([^\]]*)\]/', $html, $matches);
        foreach ($matches[1] as $attrs) {
            if (!preg_match('/\bkey=([^\s\]]+)/', $attrs, $m)) {
                throw new RuntimeException("{$where}: [claim] without key=.");
            }
            foreach (Claims::shortcodeKeys($m[1]) as $key) {
                if (!isset(Claims::CLAIMS[$key])) {
                    throw new RuntimeException("{$where}: unknown claim \"{$key}\" in [claim].");
                }
            }
        }
    }

    private function checkLink(?array $link, array $pageKeys, string $where): void
    {
        if ($link === null) {
            return;
        }
        if (!isset($pageKeys[$link['page'] ?? '']) || trim((string) ($link['text'] ?? '')) === '') {
            throw new RuntimeException("{$where}: a link needs a page key from the file and a text.");
        }
    }

    private function styleId(string $blockClass, string $templateName, string $where): int
    {
        $style = BlockStyle::get()->filter(['BlockType' => $blockClass, 'TemplateName' => $templateName])->first();
        if (!$style) {
            throw new RuntimeException("{$where}: block style {$templateName} is not registered. Run vendor/bin/sake tasks:my-task first.");
        }
        return (int) $style->ID;
    }

    // ─── Writes ──────────────────────────────────────────────────────────────

    private function seedSiteConfig(array $fields, bool $force): void
    {
        $config = SiteConfig::current_site_config();
        // A fresh SiteConfig carries the framework's placeholders ("Your Site Name", "your tagline here"): a field
        // still holding one counts as empty, not as someone's text.
        $defaults = SiteConfig::create();
        $set = 0;
        foreach ($fields as $field => $value) {
            $current = (string) $config->getField($field);
            if ($current === (string) $value) {
                continue;
            }
            $untouched = $current === '' || $current === (string) $defaults->getField($field);
            if (!$untouched && !$force) {
                $this->output->writeln("  [keep] {$field} already has text");
                continue;
            }
            $config->setField($field, $value);
            $set++;
        }
        if ($set) {
            $config->write();
        }
        $this->output->writeln("  [ok] {$set} field(s) set");
    }

    /** The page this run should fill, or null when it exists and is left alone. */
    private function seedPage(array $def, bool $force): ?SiteTree
    {
        $key = $def['key'];
        $class = $def['class'];
        $parentId = empty($def['parent']) ? 0 : (int) $this->pages[$def['parent']]->ID;
        $segment = (string) ($def['URLSegment'] ?? '');

        $page = SiteTree::get()->filter(['URLSegment' => $segment, 'ParentID' => $parentId])->first();
        if (!$page) {
            $live = Versioned::get_by_stage(SiteTree::class, Versioned::LIVE)
                ->filter(['URLSegment' => $segment, 'ParentID' => $parentId])->first();
            if ($live) {
                $this->pages[$key] = $live;
                $this->output->writeln("  [skip] {$key}: published but missing from Draft; restore it in the CMS first");
                return null;
            }
        }

        if ($page) {
            $this->pages[$key] = $page;
            if (!$force) {
                $this->output->writeln("  [skip] {$key}: exists (ID {$page->ID}); --force overwrites it");
                return null;
            }
            if (!$page instanceof $class) {
                $this->output->writeln("  [skip] {$key}: exists as {$page->ClassName}, not {$class}; change it in the CMS");
                return null;
            }
        }

        $isNew = !$page;
        $page = $page ?: $class::create();
        $page->ParentID = $parentId;
        if ($isNew) {
            // SiteTree::onBeforeWrite() places a page with no Sort after its siblings with MAX(Sort) + 1, which is
            // NULL for a parent's first child; that page then jumps to the end of the list on its next write (it
            // did: Post-Construction sorted last under Services). Append explicitly, in the file's order.
            $page->Sort = (int) SiteTree::get()->filter('ParentID', $parentId)->max('Sort') + 1;
        }
        $page->update(array_diff_key($def, array_flip(self::PAGE_KEYS)));
        $page->write();
        $this->pages[$key] = $page;
        $this->output->writeln(sprintf('  [%s] %s: %s', $isNew ? 'new' : 'overwrite', $key, $page->Link()));
        return $page;
    }

    private function seedBlocks(SiteTree $page, array $blocks): void
    {
        $area = $page->ElementalArea();
        if (!$area instanceof ElementalArea || !$area->exists()) {
            throw new RuntimeException("{$page->Title}: no block area (was the task run outside the Draft stage?)");
        }
        foreach ($blocks as $i => $def) {
            $class = $def['class'];
            $block = SeedKey::findBlock($area, $def['key']);
            if ($block && !$block instanceof $class) {
                $this->output->writeln("  [skip] {$def['key']}: a {$block->ClassName} holds this key");
                continue;
            }
            $isNew = !$block;
            $block = $block ?: $class::create();
            $block->SeedKey = $def['key'];
            $block->ParentID = $area->ID;
            $block->Sort = $i + 1;
            $block->BlockStyleID = empty($def['style']) ? 0 : $this->styleId($class, $def['style'], $def['key']);
            $block->update(array_diff_key($def, array_flip(self::BLOCK_KEYS)));
            $block->write();
            if (isset($def['cta'])) {
                $this->seedLink($block, 'CtaLink', $def['cta']);
            }
            foreach ($def['buckets'] ?? [] as $j => $bucketDef) {
                $this->seedBucket($block, $bucketDef, $j + 1);
            }
            $this->output->writeln(sprintf(
                '  [%s] %s%s%s',
                $isNew ? 'new' : 'overwrite',
                $def['key'],
                empty($def['style']) ? '' : " ({$def['style']})",
                isset($def['buckets']) ? ', ' . count($def['buckets']) . ' item(s)' : ''
            ));
        }
    }

    private function seedBucket(BaseElement $block, array $def, int $sort): void
    {
        $bucket = SeedKey::findBucket($block, $def['key']) ?: Bucket::create();
        $bucket->SeedKey = $def['key'];
        $bucket->ParentBlockID = $block->ID;
        $bucket->Sort = $sort;
        $bucket->Claims = empty($def['claims']) ? null : json_encode(array_values($def['claims']));
        $bucket->update(array_diff_key($def, array_flip(self::BUCKET_KEYS)));
        $bucket->write();
        if (isset($def['link'])) {
            $this->seedLink($bucket, 'Link', $def['link']);
        }
    }

    /** A link to one of the seeded pages, owned by $owner through its has_one $relation. */
    private function seedLink(DataObject $owner, string $relation, array $def): void
    {
        $link = $owner->getComponent($relation);
        if (!$link instanceof SiteTreeLink || !$link->exists()) {
            $link = SiteTreeLink::create();
        }
        $link->PageID = $this->pages[$def['page']]->ID;
        $link->LinkText = $def['text'];
        $link->OwnerID = $owner->ID;
        $link->OwnerClass = $owner->ClassName;
        $link->OwnerRelation = $relation;
        $link->write();
        $idField = $relation . 'ID';
        if ((int) $owner->$idField !== (int) $link->ID) {
            $owner->$idField = $link->ID;
            $owner->write();
        }
    }
}
