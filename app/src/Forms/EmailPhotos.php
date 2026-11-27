<?php

namespace App\Forms;

use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use Throwable;

/**
 * Turns the quote form's uploaded photos into email attachments, in memory: each photo is shrunk to a JPEG (longest side
 * 2,000 px, quality 80) and nothing is written to the database or to assets. PHP deletes the uploaded originals when
 * the request ends. Re-encoding also drops the photo's EXIF data, including any GPS position.
 *
 * The attachments together stay under a byte budget (15 MB), so the email stays under Mailgun's 25 MB limit once
 * base64 encoding adds a third. If the photos are too big at the first size, every photo is shrunk again at the next
 * size and quality in $steps; if they are still too big at the last step, photos are attached in order until the
 * budget is used and the rest are listed as not attached. A photo is never dropped without being listed.
 *
 * The image library is the one the CMS uses (SilverStripe's Intervention ImageManager: Imagick where the server has it,
 * otherwise GD), with its automatic rotation, so a photo taken sideways arrives upright.
 */
class EmailPhotos
{
    use Configurable;
    use Injectable;

    /** Total bytes of attachments in one email (before base64). */
    private static int $budget_bytes = 15 * 1024 * 1024;

    /** Longest side in pixels and JPEG quality, tried in order until the photos fit the budget. */
    private static array $steps = [
        [2000, 80],
        [1600, 70],
        [1280, 60],
    ];

    /**
     * Photos with more pixels than this are not opened (a 48 MP phone photo is 48.8 million). Decoding one takes about
     * 5 bytes per pixel with GD, so this cap keeps the request inside the memory set below.
     */
    private static int $max_pixels = 64_000_000;

    private const MEMORY = '512M';

    private const SECONDS = 180;

    /** Why a photo is not attached; the wording is in app/lang/en.yml. */
    public const SKIP_UNREADABLE = 'UNREADABLE';

    public const SKIP_TOO_BIG = 'TOO_MANY_PIXELS';

    public const SKIP_BUDGET = 'OVER_EMAIL_LIMIT';

    /**
     * @param array<int, array{name: string, tmp_name: string}> $files uploads in PHP's single-file shape, in order
     * @return array{
     *     attachments: list<array{name: string, data: string}>,
     *     skipped: list<array{name: string, reason: string}>
     * }
     */
    public function build(array $files): array
    {
        if (!$files) {
            return ['attachments' => [], 'skipped' => []];
        }
        Environment::increaseMemoryLimitTo(self::MEMORY);
        Environment::increaseTimeLimitTo(self::SECONDS);

        $names = $this->attachmentNames($files);
        $skipped = [];
        $readable = [];
        foreach ($files as $i => $file) {
            $reason = $this->checkSize($file);
            if ($reason) {
                $skipped[$i] = ['name' => $file['name'], 'reason' => $reason];
            } else {
                $readable[$i] = $file;
            }
        }

        $steps = static::config()->get('steps');
        $budget = (int) static::config()->get('budget_bytes');
        $encoded = [];
        foreach ($steps as [$edge, $quality]) {
            $encoded = [];
            foreach ($readable as $i => $file) {
                $data = $this->encode($file, (int) $edge, (int) $quality);
                if ($data === null) {
                    $skipped[$i] = ['name' => $file['name'], 'reason' => self::SKIP_UNREADABLE];
                    unset($readable[$i]);
                    continue;
                }
                $encoded[$i] = $data;
            }
            if (array_sum(array_map('strlen', $encoded)) <= $budget) {
                break;
            }
        }

        // Still over the budget at the last step: attach in order while they fit, list the rest.
        $attachments = [];
        $total = 0;
        foreach ($encoded as $i => $data) {
            if ($total + strlen($data) > $budget) {
                $skipped[$i] = ['name' => $files[$i]['name'], 'reason' => self::SKIP_BUDGET];
                continue;
            }
            $total += strlen($data);
            $attachments[$i] = ['name' => $names[$i], 'data' => $data];
        }

        ksort($attachments);
        ksort($skipped);
        if ($skipped) {
            $this->logger()->warning(sprintf(
                'Quote form: %d of %d photos not attached (%s).',
                count($skipped),
                count($files),
                implode(', ', array_map(fn ($s) => $s['reason'], $skipped))
            ));
        }
        return ['attachments' => array_values($attachments), 'skipped' => array_values($skipped)];
    }

    /** A reason not to open the photo at all, from its header alone; null when it may be opened. */
    private function checkSize(array $file): ?string
    {
        $info = @getimagesize($file['tmp_name']);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return self::SKIP_UNREADABLE;
        }
        if ($info[0] * $info[1] > (int) static::config()->get('max_pixels')) {
            return self::SKIP_TOO_BIG;
        }
        return null;
    }

    /** The photo as JPEG bytes, longest side at most $edge; null when the image library cannot read it. */
    private function encode(array $file, int $edge, int $quality): ?string
    {
        try {
            /** @var ImageManager $manager */
            $manager = Injector::inst()->create(ImageManager::class);
            $image = $manager->read($file['tmp_name']);
            $image->scaleDown($edge, $edge);
            $data = (string) $image->toJpeg(quality: $quality, strip: true);
            unset($image);
            return $data !== '' ? $data : null;
        } catch (Throwable $e) {
            $this->logger()->error('Quote form: a photo could not be read: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Attachment names in upload order: 01-<original name>.jpg. The number keeps the visitor's order in a mail client
     * that sorts attachments by name, and keeps two photos with the same name apart.
     *
     * @return array<int, string>
     */
    private function attachmentNames(array $files): array
    {
        $names = [];
        $width = max(2, strlen((string) count($files)));
        $n = 0;
        foreach ($files as $i => $file) {
            $base = pathinfo((string) $file['name'], PATHINFO_FILENAME);
            $base = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $base), '-.');
            $base = substr($base, 0, 60) ?: 'photo';
            $names[$i] = str_pad((string) ++$n, $width, '0', STR_PAD_LEFT) . '-' . $base . '.jpg';
        }
        return $names;
    }

    private function logger(): LoggerInterface
    {
        return Injector::inst()->get(LoggerInterface::class);
    }
}
