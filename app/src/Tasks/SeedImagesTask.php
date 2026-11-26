<?php

namespace App\Tasks;

use App\Models\SeedKey;
use DNADesign\Elemental\Models\ElementalArea;
use RuntimeException;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

/**
 * Attaches the launch photos listed in app/seed/images.yml to the pages, blocks and items the content seed built
 * (App\Tasks\SeedTask), and fills in the footer's photo credits.
 *
 *   vendor/bin/sake tasks:seed-images              drafts only; fills empty image fields only
 *   vendor/bin/sake tasks:seed-images --publish    also publishes every record this run changed
 *   vendor/bin/sake tasks:seed-images --force      also replaces an image someone already chose
 *
 * Safe by default: an image field that already holds an image is left alone, so a re-run never undoes an editor's
 * choice, and the photo credits are set only while the field is empty or still holds the sentence the content seed
 * wrote. A photo already in Files (same folder and name) is reused, never uploaded twice. Nothing is deleted.
 *
 * The alt text is stored as the image's Title, which SilverStripe prints as the alt attribute. Records are found
 * by URL path and SeedKey, never by Title. The whole list is checked before anything is written: a missing file,
 * empty alt text, unknown page, block or item, or a field that is not an image stops the task with nothing changed.
 */
class SeedImagesTask extends BuildTask
{
    // Writes content: CLI only, so no CMS user can run it from /dev/tasks on a live site.
    private static bool $can_run_in_browser = false;

    protected static string $commandName = 'seed-images';

    protected string $title = 'Seed launch photos';

    protected static string $description = 'Attach the launch photos in app/seed/images.yml and set the photo credits. '
        . 'Fills empty image fields only unless --force. Drafts only unless --publish.';

    private const LIST_FILE = 'app/seed/images.yml';

    private const IMAGE_DIR = 'app/seed/images';

    private const CONTENT_FILE = 'app/seed/content.yml';

    private const EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png'];

    private PolyOutput $output;

    public function getOptions(): array
    {
        return [
            new InputOption('publish', null, InputOption::VALUE_NONE, 'Publish every record this run changed (default: drafts only)'),
            new InputOption('force', null, InputOption::VALUE_NONE, 'Replace images and photo credits someone already set. Never on a site the client edits.'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->output = $output;
        $force = (bool) $input->getOption('force');
        $publish = (bool) $input->getOption('publish');

        $path = BASE_PATH . '/' . self::LIST_FILE;
        if (!is_file($path)) {
            $output->writeln("[stop] {$path} not found");
            return 1;
        }
        $list = Yaml::parseFile($path);

        return Versioned::withVersionedMode(function () use ($list, $force, $publish): int {
            // Blocks and items live in Draft first, as the content seed wrote them.
            Versioned::set_stage(Versioned::DRAFT);

            try {
                $plan = $this->check($list);
            } catch (RuntimeException $e) {
                $this->output->writeln('[stop] ' . $e->getMessage() . ' Nothing was written.');
                return 1;
            }

            $this->output->writeln('--- Photo credits ---');
            $this->seedCredits((string) $list['credits'], $force);

            $this->output->writeln('--- Photos ---');
            $changed = [];
            foreach ($plan as [$entry, $record, $field]) {
                if ($this->attach($entry, $record, $field, $force)) {
                    $changed[] = $record;
                }
            }

            if ($publish && $changed) {
                $this->output->writeln('--- Publishing ---');
                foreach ($changed as $record) {
                    // publishRecursive() also publishes what the record owns: the photo itself (a draft-only file
                    // is not served to visitors) and, for a page, its blocks.
                    $record->publishRecursive();
                    $this->output->writeln(sprintf('  [published] %s #%d', $record->ClassName, $record->ID));
                }
            }

            $this->output->writeln(sprintf(
                'Done: %d field(s) set%s, %d left as they were.',
                count($changed),
                $publish ? ' and published' : ' as drafts',
                count($plan) - count($changed)
            ));
            return 0;
        });
    }

    // ─── Checks (before any write) ───────────────────────────────────────────

    /** @return array<int, array{0: array, 1: DataObject, 2: string}> entry, record that shows the photo, field */
    private function check(array $list): array
    {
        if (trim((string) ($list['credits'] ?? '')) === '') {
            throw new RuntimeException('credits is empty: name the photographers of the photos in the list.');
        }
        $plan = [];
        $seen = [];
        foreach ($list['images'] ?? [] as $i => $entry) {
            $file = (string) ($entry['file'] ?? '');
            $where = 'Photo ' . ($file !== '' ? $file : '#' . ($i + 1));

            if ($file === '' || basename($file) !== $file
                || !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
                throw new RuntimeException("{$where}: file must be a plain file name ending in " . implode(', ', self::EXTENSIONS) . '.');
            }
            if (!is_file(BASE_PATH . '/' . self::IMAGE_DIR . '/' . $file)) {
                throw new RuntimeException("{$where}: not found in " . self::IMAGE_DIR . '/.');
            }
            if (trim((string) ($entry['alt'] ?? '')) === '') {
                throw new RuntimeException("{$where}: alt text is empty.");
            }
            if (!preg_match('#^[A-Za-z0-9][A-Za-z0-9 _/-]*$#', (string) ($entry['folder'] ?? ''))) {
                throw new RuntimeException("{$where}: folder must be a plain folder path such as Hero or Blocks/Images.");
            }

            $page = SiteTree::get_by_link((string) ($entry['path'] ?? ''));
            if (!$page) {
                throw new RuntimeException("{$where}: no page at " . ($entry['path'] ?? '(no path)') . '.');
            }
            $record = $page;
            if (!empty($entry['block'])) {
                $area = $page->hasMethod('ElementalArea') ? $page->ElementalArea() : null;
                $record = $area instanceof ElementalArea ? SeedKey::findBlock($area, (string) $entry['block']) : null;
                if (!$record) {
                    throw new RuntimeException("{$where}: no block \"{$entry['block']}\" on {$entry['path']}.");
                }
                if (!empty($entry['bucket'])) {
                    $record = SeedKey::findBucket($record, (string) $entry['bucket']);
                    if (!$record) {
                        throw new RuntimeException("{$where}: no item \"{$entry['bucket']}\" in block \"{$entry['block']}\".");
                    }
                }
            }

            $field = (string) ($entry['field'] ?? '');
            $class = $field === '' ? null : DataObject::getSchema()->hasOneComponent(get_class($record), $field);
            if (!$class || !is_a($class, File::class, true) || !is_a(Image::class, $class, true)) {
                throw new RuntimeException("{$where}: {$record->ClassName} has no image field \"{$field}\".");
            }

            $target = $record->ClassName . '#' . $record->ID . '.' . $field;
            if (isset($seen[$target])) {
                throw new RuntimeException("{$where}: {$target} is filled by two photos in the list.");
            }
            $seen[$target] = true;
            $plan[] = [$entry, $record, $field];
        }
        if (!$plan) {
            throw new RuntimeException('the list has no images.');
        }
        return $plan;
    }

    // ─── Writes ──────────────────────────────────────────────────────────────

    /**
     * Settings > Footer > Photo credits, while it is empty or still holds the content seed's sentence (nobody has
     * edited it yet), or with --force.
     */
    private function seedCredits(string $credits, bool $force): void
    {
        $config = SiteConfig::current_site_config();
        $current = trim((string) $config->PhotoCredits);
        $content = Yaml::parseFile(BASE_PATH . '/' . self::CONTENT_FILE);
        $seeded = trim((string) ($content['siteconfig']['PhotoCredits'] ?? ''));

        if ($current === trim($credits)) {
            $this->output->writeln('  [ok] already set');
            return;
        }
        if ($current !== '' && $current !== $seeded && !$force) {
            $this->output->writeln('  [keep] someone has edited the photo credits; --force replaces them');
            return;
        }
        $config->PhotoCredits = trim($credits);
        $config->write();
        $this->output->writeln('  [ok] set');
    }

    /** True when the record's image field was changed. */
    private function attach(array $entry, DataObject $record, string $field, bool $force): bool
    {
        $label = $entry['file'] . ' -> ' . $entry['path'] . (empty($entry['block']) ? '' : ' ' . $entry['block'])
            . (empty($entry['bucket']) ? '' : '/' . $entry['bucket']) . ' ' . $field;
        $image = $this->image($entry);
        $idField = $field . 'ID';

        if ((int) $record->$idField === (int) $image->ID) {
            $this->output->writeln("  [ok] {$label}: already set");
            return false;
        }
        if ((int) $record->$idField !== 0 && !$force) {
            $this->output->writeln("  [keep] {$label}: holds another image; --force replaces it");
            return false;
        }
        $record->$idField = $image->ID;
        $record->write();
        $this->output->writeln("  [set] {$label}");
        return true;
    }

    /** The photo in Files (folder + name), uploaded from app/seed/images/ the first time. */
    private function image(array $entry): Image
    {
        $folder = Folder::find_or_make(trim((string) $entry['folder'], '/'));
        $existing = Image::get()->filter(['ParentID' => $folder->ID, 'Name' => $entry['file']])->first();
        if ($existing) {
            return $existing;
        }
        $image = Image::create();
        $image->setFromLocalFile(BASE_PATH . '/' . self::IMAGE_DIR . '/' . $entry['file'], $folder->getFilename() . $entry['file']);
        $image->ParentID = $folder->ID;
        $image->Title = trim((string) $entry['alt']);
        $image->write();
        return $image;
    }
}
