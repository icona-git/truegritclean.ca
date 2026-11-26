<?php

namespace App\Control;

use App\Pages\ContactPage;
use App\Pages\PracticeAreaPage;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * /llms.txt: a plain-text summary of the business for AI assistants (llmstxt.org format). Who, what, where,
 * the services, the exclusions and how to get in touch, built on every request from Settings and the
 * published pages, so it never drifts from the site. It carries no copy of its own: every sentence comes
 * from the CMS (the home page's meta description, Settings > Main/Contact Info/Footer, each page's meta
 * description). Only the section headings are written here.
 */
class LlmsTxtController extends Controller
{
    public function index(): HTTPResponse
    {
        $siteConfig = SiteConfig::current_site_config();
        $base = rtrim(Director::absoluteBaseURL(), '/') . '/';
        $lines = [];

        $name = $this->clean($siteConfig->Title);
        $lines[] = '# ' . ($name !== '' ? $name : $base);
        $lines[] = '';

        $home = SiteTree::get_by_link(RootURLController::get_homepage_link());
        $summary = $home ? $this->clean($home->MetaDescription) : '';
        if ($summary !== '') {
            $lines[] = '> ' . $summary;
            $lines[] = '';
        }
        $tagline = $this->clean($siteConfig->Tagline);
        if ($tagline !== '' && strtolower($tagline) !== 'your tagline here') {
            $lines[] = $tagline;
            $lines[] = '';
        }

        $contact = [];
        $area = $this->clean($siteConfig->ServiceArea);
        if ($area !== '') {
            $contact[] = '- Service area: ' . $area;
        }
        $phone = $this->clean($siteConfig->Phone);
        if ($phone !== '') {
            $contact[] = '- Phone: ' . $phone;
        }
        $email = $this->clean($siteConfig->Email);
        if ($email !== '') {
            $contact[] = '- Email: ' . $email;
        }
        $contact[] = '- Website: ' . $base;
        $quote = ContactPage::get()->first();
        if ($quote && $quote->canView()) {
            $contact[] = '- ' . $this->clean($quote->MenuTitle ?: $quote->Title) . ': ' . $quote->AbsoluteLink();
        }
        $lines[] = '## Contact';
        $lines[] = '';
        array_push($lines, ...$contact);
        $lines[] = '';

        $services = [];
        $servicePages = PracticeAreaPage::get()->filter('ShowInSearch', true)->sort(['ParentID' => 'ASC', 'Sort' => 'ASC']);
        $listed = [];
        foreach ($servicePages as $page) {
            if (!$page->canView()) {
                continue;
            }
            $parent = $page->ParentID ? $page->Parent() : null;
            if ($parent && $parent->exists() && !isset($listed[$parent->ID]) && $parent->canView() && $parent->ShowInSearch) {
                $services[] = $this->linkLine($parent);
                $listed[$parent->ID] = true;
            }
            $services[] = $this->linkLine($page);
            $listed[$page->ID] = true;
        }
        if ($services) {
            $lines[] = '## Services';
            $lines[] = '';
            array_push($lines, ...$services);
            $lines[] = '';
        }

        $exclusions = $this->clean($siteConfig->FooterExclusions);
        if ($exclusions !== '') {
            $lines[] = '## Exclusions';
            $lines[] = '';
            $lines[] = $exclusions;
            $lines[] = '';
        }

        $pages = [];
        foreach (SiteTree::get()->filter(['ParentID' => 0, 'ShowInSearch' => true])->sort('Sort') as $page) {
            if ($page instanceof ErrorPage || isset($listed[$page->ID]) || !$page->canView()) {
                continue;
            }
            if ($home && $page->ID === $home->ID) {
                continue;
            }
            $pages[] = $this->linkLine($page);
        }
        if ($pages) {
            $lines[] = '## Pages';
            $lines[] = '';
            array_push($lines, ...$pages);
            $lines[] = '';
        }

        $response = HTTPResponse::create(implode("\n", $lines));
        $response->addHeader('Content-Type', 'text/plain; charset=utf-8');
        return $response;
    }

    /** "- [Title](url): meta description", or without the description when the page has none. */
    private function linkLine(SiteTree $page): string
    {
        $title = str_replace(['[', ']'], '', $this->clean($page->Title));
        $line = '- [' . $title . '](' . $page->AbsoluteLink() . ')';
        $description = $this->clean($page->MetaDescription);
        return $description !== '' ? $line . ': ' . $description : $line;
    }

    /** CMS text to one plain line: tags stripped, entities decoded, whitespace (line breaks too) collapsed. */
    private function clean($value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
