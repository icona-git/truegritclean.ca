<?php

namespace App\Tasks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Config\Collections\MutableConfigCollectionInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;
use App\Models\BlockStyle;

/**
 * Kitchen sink, run once on every new site after the first db:build, and safe to re-run:
 * - registers every block variant (BlockStyle rows) so the CMS dropdown and CustomRender() find them;
 * - makes sure the 404 and 500 error pages exist.
 * Page and block CONTENT is seeded by a separate task written for each site (SeedTask); this task
 * never carries a firm's copy.
 */
class MyTask extends BuildTask
{
    // Setup, not a page to browse: this task writes. It runs from the CLI only, so no CMS user can
    // trigger it from /dev/tasks on a live site.
    private static bool $can_run_in_browser = false;

    protected static string $commandName = 'my-task';
    protected string $title = 'Kitchen Sink';
    protected static string $description = 'Seed the BlockStyle registry (block variants) and the 404/500 error pages. Idempotent.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->seedBlockStyles($output);
        return $this->ensureErrorPages($output);
    }

    /**
     * app/_config/mysite.yml turns create_default_pages off so db:build never invents Home, About and
     * Contact pages. That switch ALSO stops ErrorPage creating its 404 and 500 pages, and without them a
     * missing URL gets a bare one-line response and no static error file is written. This runs the
     * module's own error-page setup with the switch on for that call only; SiteTree's default pages
     * are made only when the class being set up is SiteTree itself, so none are created here.
     */
    private function ensureErrorPages(PolyOutput $output): int
    {
        $output->writeln('--- Ensuring the 404 and 500 error pages ---');
        Config::withConfig(function (MutableConfigCollectionInterface $config) {
            $config->set(SiteTree::class, 'create_default_pages', true);
            ErrorPage::singleton()->requireDefaultRecords();
        });
        $missing = 0;
        foreach ([404, 500] as $code) {
            $page = ErrorPage::get()->filter('ErrorCode', $code)->first();
            $output->writeln($page ? "  [ok] {$code}: {$page->Title}" : "  [missing] {$code}");
            $missing += $page ? 0 : 1;
        }
        // A task that reports [missing] and still exits 0 is a task nobody notices failing.
        return $missing === 0 ? 0 : 1;
    }

    private function seedBlockStyles(PolyOutput $output): void
    {
        $output->writeln('--- Seeding BlockStyle records ---');
        $defs = [
            ['Section Content - Split',            'App\\Elements\\SectionContentBlock', 'Split'],
            ['Section Content - Banner CTA',       'App\\Elements\\SectionContentBlock', 'BannerCta'],
            ['Section Content - Banner CTA Right', 'App\\Elements\\SectionContentBlock', 'BannerCtaRight'],
            ['Section Content - Centered',         'App\\Elements\\SectionContentBlock', 'Centered'],
            ['Section Content - Why Choose Us',    'App\\Elements\\SectionContentBlock', 'WhyChoose'],
            ['Section Content - Approach',         'App\\Elements\\SectionContentBlock', 'Approach'],
            ['Section Content - Dark Split',       'App\\Elements\\SectionContentBlock', 'DarkSplit'],
            ['Section Content - Offset Text',      'App\\Elements\\SectionContentBlock', 'OffsetText'],
            ['Section Content - Dark Body',        'App\\Elements\\SectionContentBlock', 'DarkBody'],
            ['Section Content - Dark CTA',         'App\\Elements\\SectionContentBlock', 'DarkCta'],
            ['Section Content - Photo Box',        'App\\Elements\\SectionContentBlock', 'PhotoBox'],
            ['Section Content - Right Text',       'App\\Elements\\SectionContentBlock', 'RightText'],
            ['Buckets - Columns',                  'App\\Elements\\BucketsBlock', 'Columns'],
            ['Buckets - Columns 2',                'App\\Elements\\BucketsBlock', 'Columns2'],
            ['Buckets - Checkerboard',             'App\\Elements\\BucketsBlock', 'Checkerboard'],
            ['Buckets - Text Cards',               'App\\Elements\\BucketsBlock', 'TextCards'],
            ['Buckets - Experience',               'App\\Elements\\BucketsBlock', 'Experience'],
            ['Buckets - Intro',                    'App\\Elements\\BucketsBlock', 'Intro'],
            ['Buckets - Intro With Bullets',       'App\\Elements\\BucketsBlock', 'IntroWithBullets'],
            ['Buckets - Our Approach',             'App\\Elements\\BucketsBlock', 'OurApproach'],
            ['Buckets - Payment Panels',           'App\\Elements\\BucketsBlock', 'PaymentPanels'],
            ['Buckets - Dark Columns',             'App\\Elements\\BucketsBlock', 'DarkColumns'],
            ['Buckets - FAQ',                      'App\\Elements\\BucketsBlock', 'Faq'],
            ['Blog Posts',                         'App\\Elements\\SpecialListBlock', 'BlogPosts'],
            ['Services',                           'App\\Elements\\SpecialListBlock', 'Services'],
            ['Team Grid',                          'App\\Elements\\PersonBlock', 'Grid'],
            ['Person List',                        'App\\Elements\\PersonBlock', 'List'],
            ['Our Counsel',                        'App\\Elements\\PersonBlock', 'OurCounsel'],
            // truegritclean.ca: one variant per Figma section (design/figma-export). Templates are
            // themes/app-theme/templates/App/Elements/<BlockClass>_<TemplateName>.ss; css/src/blocks/_truegrit.scss
            // bridges them to the ported Figma styles.
            ['Section Content - Industry Band',    'App\\Elements\\SectionContentBlock', 'IndustryBand'],
            ['Section Content - Service Intro',    'App\\Elements\\SectionContentBlock', 'ServiceIntro'],
            ['Section Content - Tags',             'App\\Elements\\SectionContentBlock', 'Tags'],
            ['Section Content - Callout',          'App\\Elements\\SectionContentBlock', 'Callout'],
            ['Section Content - Feature Image',    'App\\Elements\\SectionContentBlock', 'FeatureImage'],
            ['Buckets - Service Cards',            'App\\Elements\\BucketsBlock', 'ServiceCards'],
            ['Buckets - Phases',                   'App\\Elements\\BucketsBlock', 'Phases'],
            ['Buckets - Service Phases',           'App\\Elements\\BucketsBlock', 'ServicePhases'],
            ['Buckets - Checklist',                'App\\Elements\\BucketsBlock', 'Checklist'],
            ['Buckets - Steps',                    'App\\Elements\\BucketsBlock', 'Steps'],
            ['Buckets - Scope Groups',             'App\\Elements\\BucketsBlock', 'ScopeGroups'],
            ['Buckets - Audience Cards',           'App\\Elements\\BucketsBlock', 'AudienceCards'],
            ['Buckets - Standards',                'App\\Elements\\BucketsBlock', 'Standards'],
            ['Buckets - Story',                    'App\\Elements\\BucketsBlock', 'Story'],
            ['Buckets - Team',                     'App\\Elements\\BucketsBlock', 'Team'],
            ['Buckets - Quote Assurance',          'App\\Elements\\BucketsBlock', 'Assurance'],
        ];
        foreach ($defs as [$title, $blockType, $templateName]) {
            $style = BlockStyle::get()->filter(['BlockType' => $blockType, 'TemplateName' => $templateName])->first();
            if ($style) {
                $output->writeln("  [skip] {$title}");
                continue;
            }
            $style = BlockStyle::create();
            $style->Title = $title;
            $style->BlockType = $blockType;
            $style->TemplateName = $templateName;
            $style->write();
            $output->writeln("  [ok] {$title}");
        }
    }
}
