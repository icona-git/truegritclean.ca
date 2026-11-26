<?php

namespace App\Extensions;

use App\Elements\BucketsBlock;
use App\Pages\ContactPage;
use App\Pages\CustomBlog;
use App\Pages\CustomBlogPost;
use App\Pages\PersonPage;
use App\Pages\PracticeAreaPage;
use SilverStripe\Blog\Model\BlogController;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Site-wide SEO head layer for SiteTree pages. Runs AFTER syntro/silverstripe-seo
 * (see app/_config/seo.yml) and adds what that module does not:
 *
 *  - <title> pattern           getSEOPageTitle(): MetaTitle, else "Title | Firm name"
 *  - meta description fallback derived from teaser / summary / body when the editor left it blank
 *  - rel=canonical             self-referencing, query string stripped (?start=N kept for pagination)
 *  - Open Graph + Twitter Card tags with a guarded share image
 *  - noindex on thin blog listings (tag and date-archive views)
 *  - JSON-LD enrichment        updateSchemaGraph(): the business node (type from business_type, with
 *                              slogan, logo and the catalogue of service pages), Person, Service,
 *                              BlogPosting, FAQPage, breadcrumb names fixed to plain page titles
 *  - manual SchemaJson field   still replaces the generated graph entirely (legacy behaviour)
 *
 * Everything is null-safe: a missing image or relation costs a tag, never a 500.
 */
class SEOMetaExtension extends Extension
{
    /** Populated from app/_config/seo.yml. Feeds the business node's areaServed and every Service node. */
    private static $area_served = [];

    /** schema.org type of the site-wide business node. The starter's default, LegalService, is for law firms. */
    private static $business_type = 'LegalService';

    /**
     * A service-area business: the schema never carries a street address or postal code, even when
     * Settings > Contact Info holds one. City and province still appear when filled in.
     */
    private static $service_area_business = false;

    /** Short brand used as the <title> suffix on blog posts, where the full firm name would overrun 60 chars. */
    private static $short_brand = '';

    private static $og_locale = 'en_CA';

    private static $schema_language = 'en-CA';

    /** @var string|null|false */
    private $shareImageCache = false;

    // ─── Title ─────────────────────────────────────────────────────────────────

    /**
     * The <title> tag text. Editors override per page with the SEO tab "Meta title".
     */
    public function getSEOPageTitle(): string
    {
        $owner = $this->getOwner();
        $meta = trim((string) $owner->MetaTitle);
        if ($meta !== '') {
            return $meta;
        }
        $siteConfig = SiteConfig::current_site_config();
        $brand = trim((string) $siteConfig->Title);
        if ($this->isHome()) {
            $tagline = trim((string) $siteConfig->Tagline);
            if ($tagline !== '' && strtolower($tagline) !== 'your tagline here') {
                return $brand . ' | ' . $tagline;
            }
            return $brand !== '' ? $brand : (string) $owner->Title;
        }
        $title = trim((string) $owner->Title);
        if ($owner instanceof CustomBlogPost) {
            $short = trim((string) Config::inst()->get(self::class, 'short_brand'));
            if ($short !== '') {
                $brand = $short;
            }
        }
        return $brand !== '' ? $title . ' | ' . $brand : $title;
    }

    // ─── Description ───────────────────────────────────────────────────────────

    /**
     * Meta description: the editor's MetaDescription, else plain text derived from the most
     * specific real content the page has. Never invented.
     */
    public function getSEODescription(): string
    {
        $owner = $this->getOwner();
        $meta = trim((string) $owner->MetaDescription);
        if ($meta !== '') {
            return $meta;
        }
        $siteConfig = SiteConfig::current_site_config();
        $candidates = [];

        if ($owner instanceof PersonPage) {
            $candidates[] = $owner->Content;
            // "Name, Job Title at Firm. Tagline." - every part is CMS data.
            $bits    = array_filter([trim((string) $owner->Title), trim((string) $owner->JobTitle)]);
            $firm    = trim((string) $siteConfig->Title);
            $tagline = trim((string) $siteConfig->Tagline);
            $line    = implode(', ', $bits) . ($firm !== '' ? ' at ' . $firm : '') . '.';
            if ($tagline !== '' && strtolower($tagline) !== 'your tagline here') {
                $line .= ' ' . rtrim($tagline, '.') . '.';
            }
            $candidates[] = $line;
        } elseif ($owner instanceof CustomBlogPost) {
            $candidates[] = $owner->Summary;
            $candidates[] = $owner->Content;
        } else {
            $candidates[] = $owner->TeaserContent ?? null;
            $candidates[] = $owner->Content;
            $candidates[] = $owner->HeroContent ?? null;
            $candidates[] = $siteConfig->Tagline;
        }

        foreach ($candidates as $html) {
            $text = $this->plainText($html, 158);
            if ($text !== '' && strtolower($text) !== 'your tagline here') {
                return $text;
            }
        }
        return '';
    }

    // ─── Canonical ─────────────────────────────────────────────────────────────

    public function getSEOCanonicalURL(): ?string
    {
        $owner = $this->getOwner();
        if (!$owner->exists() || $owner instanceof ErrorPage) {
            return null;
        }
        $request = $this->currentRequest();
        $relative = $request ? $request->getURL(false) : null;

        if ($this->isHome()) {
            $trimmed = trim((string) $relative, '/');
            if ($trimmed === '' || $trimmed === $owner->URLSegment) {
                return rtrim(Director::absoluteBaseURL(), '/') . '/';
            }
        }

        $target = ($relative !== null && $relative !== '') ? $relative : $owner->Link();
        $url = Director::absoluteURL($target);
        $url = explode('?', $url, 2)[0];

        $start = $request ? $request->getVar('start') : null;
        if ($start !== null && is_scalar($start) && ctype_digit((string) $start) && (int) $start > 0) {
            $url .= '?start=' . (int) $start;
        }
        return $url;
    }

    // ─── Share image ───────────────────────────────────────────────────────────

    /**
     * Absolute URL of the best sharing image for this page, or null.
     * Order: page hero, teaser, person headshot, blog featured image, site default share image, company logo.
     */
    public function getSEOShareImage(): ?string
    {
        if ($this->shareImageCache !== false) {
            return $this->shareImageCache;
        }
        $this->shareImageCache = null;
        $owner = $this->getOwner();
        $siteConfig = SiteConfig::current_site_config();

        $sources = [
            ['obj' => $owner, 'rel' => 'Headshot', 'fill' => false],
            ['obj' => $owner, 'rel' => 'HeroImage', 'fill' => true],
            ['obj' => $owner, 'rel' => 'TeaserImage', 'fill' => true],
            ['obj' => $owner, 'rel' => 'FeaturedImage', 'fill' => true],
            ['obj' => $siteConfig, 'rel' => 'ShareImage', 'fill' => true],
            ['obj' => $siteConfig, 'rel' => 'CompanyLogo', 'fill' => false],
        ];
        foreach ($sources as $source) {
            $url = $this->imageURL($source['obj'], $source['rel'], $source['fill']);
            if ($url) {
                $this->shareImageCache = $url;
                break;
            }
        }
        return $this->shareImageCache;
    }

    private function imageURL($obj, string $relation, bool $fill): ?string
    {
        try {
            if (!$obj || !$obj->hasMethod($relation)) {
                return null;
            }
            $idField = $relation . 'ID';
            if (!$obj->$idField) {
                return null;
            }
            $image = $obj->$relation();
            if (!$image || !$image->exists() || !$image->hasMethod('getAbsoluteURL')) {
                return null;
            }
            if ($fill && $image->hasMethod('Fill') && $image->getIsImage()) {
                $resized = $image->Fill(1200, 630);
                if ($resized) {
                    return $resized->getAbsoluteURL();
                }
            }
            return $image->getAbsoluteURL();
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ─── Meta components (title, description, canonical, OG, robots) ───────────

    public function updateMetaComponents(&$tags)
    {
        $owner = $this->getOwner();

        if ($owner instanceof ErrorPage) {
            unset($tags['graphld']);
            return;
        }

        $description = $this->getSEODescription();
        if (empty($tags['description']) && $description !== '') {
            $tags['description'] = [
                'attributes' => ['name' => 'description', 'content' => $description],
            ];
        }

        $canonical = $this->getSEOCanonicalURL();
        if ($canonical) {
            $tags['canonical'] = [
                'tag' => 'link',
                'attributes' => ['rel' => 'canonical', 'href' => $canonical],
            ];
        }

        if ($this->isThinBlogListing()) {
            $tags['robots'] = [
                'attributes' => ['name' => 'robots', 'content' => 'noindex, follow'],
            ];
        }

        $siteConfig = SiteConfig::current_site_config();
        $title = $this->getSEOPageTitle();
        $image = $this->getSEOShareImage();
        $ogType = 'website';
        if ($owner instanceof CustomBlogPost) {
            $ogType = 'article';
        } elseif ($owner instanceof PersonPage) {
            $ogType = 'profile';
        }

        $og = [
            'og:type' => $ogType,
            'og:site_name' => (string) $siteConfig->Title,
            'og:locale' => (string) Config::inst()->get(self::class, 'og_locale'),
            'og:title' => $title,
            'og:description' => $description,
            'og:url' => $canonical,
            'og:image' => $image,
        ];
        if ($owner instanceof CustomBlogPost) {
            $og['article:published_time'] = $this->isoDate($owner->PublishDate);
            $og['article:modified_time'] = $this->isoDate($owner->LastEdited);
        }
        if ($owner instanceof PersonPage) {
            $og['profile:first_name'] = (string) $owner->FirstName;
            $og['profile:last_name'] = (string) $owner->LastName;
        }
        foreach ($og as $property => $content) {
            if ($content === null || trim((string) $content) === '') {
                continue;
            }
            $tags['og_' . str_replace(':', '_', $property)] = [
                'attributes' => ['property' => $property, 'content' => $content],
            ];
        }

        $twitter = [
            'twitter:card' => $image ? 'summary_large_image' : 'summary',
            'twitter:title' => $title,
            'twitter:description' => $description,
            'twitter:image' => $image,
        ];
        foreach ($twitter as $name => $content) {
            if ($content === null || trim((string) $content) === '') {
                continue;
            }
            $tags['tw_' . str_replace(':', '_', $name)] = [
                'attributes' => ['name' => $name, 'content' => $content],
            ];
        }

        // A hand-written SchemaJson block (SEO tab > Schema) replaces the generated graph entirely.
        if (!empty($owner->SchemaJson)) {
            unset($tags['graphld']);
        }
    }

    // ─── JSON-LD graph enrichment ──────────────────────────────────────────────

    /**
     * Hook called by Syntro\SEO\Extensions\SEOExtension::getSchemaGraph() with
     * ['@context' => ..., '@graph' => [Organization, WebSite, WebPage, BreadcrumbList]].
     */
    public function updateSchemaGraph(&$graph)
    {
        $owner = $this->getOwner();
        if (!isset($graph['@graph']) || !is_array($graph['@graph'])) {
            return;
        }
        $language = (string) Config::inst()->get(self::class, 'schema_language');
        $siteConfig = SiteConfig::current_site_config();
        $base = Director::absoluteBaseURL();
        $orgId = $base . '#organization';
        $pageUrl = $this->getSEOCanonicalURL() ?: $owner->AbsoluteLink();
        $webPageId = null;
        $nodes = [];

        foreach ($graph['@graph'] as $node) {
            if (!is_array($node) || !isset($node['@type'])) {
                $nodes[] = $node;
                continue;
            }
            $type = is_array($node['@type']) ? ($node['@type'][0] ?? '') : $node['@type'];
            if (isset($node['inLanguage'])) {
                $node['inLanguage'] = $language;
            }
            switch ($type) {
                case 'Organization':
                    $orgId = $node['@id'] ?? $orgId;
                    $node = $this->businessNode($node, $siteConfig, $base);
                    break;
                case 'WebSite':
                    $node['inLanguage'] = $language;
                    if (empty($node['description']) || strtolower((string) $node['description']) === 'your tagline here') {
                        unset($node['description']);
                    }
                    break;
                case 'WebPage':
                    $webPageId = $node['@id'] ?? ($pageUrl . '#webpage');
                    $node = $this->webPageNode($node, $language);
                    break;
                case 'BreadcrumbList':
                    $node = $this->breadcrumbNode($node, $base);
                    break;
            }
            $nodes[] = $node;
        }

        if ($owner instanceof PersonPage) {
            $person = $this->personNode($owner, $orgId, $pageUrl);
            $nodes = $this->retypeWebPage($nodes, 'ProfilePage', ['mainEntity' => ['@id' => $person['@id']]]);
            $nodes[] = $person;
        } elseif ($owner instanceof PracticeAreaPage) {
            $service = $this->serviceNode($owner, $orgId, $pageUrl);
            $nodes = $this->retypeWebPage($nodes, null, ['about' => ['@id' => $service['@id']]]);
            $nodes[] = $service;
        } elseif ($owner instanceof CustomBlogPost) {
            $nodes[] = $this->blogPostingNode($owner, $orgId, $pageUrl, $webPageId, $language);
        } elseif ($owner instanceof ContactPage) {
            $nodes = $this->retypeWebPage($nodes, 'ContactPage', []);
        } elseif ($owner instanceof CustomBlog) {
            $nodes = $this->retypeWebPage($nodes, 'CollectionPage', []);
        }

        $faq = $this->faqQuestions($owner);
        if ($faq) {
            $nodes = $this->retypeWebPage($nodes, 'FAQPage', ['mainEntity' => $faq], true);
        }

        $graph['@graph'] = array_values($nodes);
    }

    private function businessNode(array $node, SiteConfig $siteConfig, string $base): array
    {
        $type = trim((string) Config::inst()->get(self::class, 'business_type'));
        $node['@type'] = $type !== '' ? $type : 'LegalService';
        $node['name'] = (string) $siteConfig->Title;
        $node['url'] = $base;
        $phone = $this->schemaPhone($siteConfig);
        $this->addIfNotEmpty($node, 'telephone', $phone);
        $this->addIfNotEmpty($node, 'faxNumber', $siteConfig->Fax);
        $this->addIfNotEmpty($node, 'email', $siteConfig->Email);
        $tagline = trim((string) $siteConfig->Tagline);
        if ($tagline !== '' && strtolower($tagline) !== 'your tagline here') {
            $node['slogan'] = $tagline;
        }

        // A service-area business never publishes a street address or postal code, whatever Settings holds.
        $serviceAreaOnly = (bool) Config::inst()->get(self::class, 'service_area_business');
        $address = ['@type' => 'PostalAddress'];
        if (!$serviceAreaOnly) {
            $this->addIfNotEmpty($address, 'streetAddress', $siteConfig->StreetAddress);
        }
        $this->addIfNotEmpty($address, 'addressLocality', $siteConfig->City);
        $this->addIfNotEmpty($address, 'addressRegion', $siteConfig->Province);
        if (!$serviceAreaOnly) {
            $this->addIfNotEmpty($address, 'postalCode', $siteConfig->PostalCode);
        }
        if (count($address) > 1) {
            $address['addressCountry'] = 'CA';
            $node['address'] = $address;
        } else {
            unset($node['address']);
        }

        $areaServed = $this->areaServed();
        if ($areaServed) {
            $node['areaServed'] = $areaServed;
        }

        // The module points logo at a relative URL; search engines want an absolute one.
        unset($node['logo'], $node['image']);
        $logo = $this->logoNode($siteConfig, $base);
        if ($logo) {
            $node['logo'] = $logo;
            $node['image'] = ['@id' => $logo['@id']];
        }

        $catalog = $this->offerCatalog();
        if ($catalog) {
            $node['hasOfferCatalog'] = $catalog;
        }

        if ($phone !== '') {
            $node['contactPoint'] = [
                '@type' => 'ContactPoint',
                'telephone' => $phone,
                'contactType' => 'customer service',
                'areaServed' => 'CA',
                'availableLanguage' => 'en',
            ];
        }

        $sameAs = array_values(array_filter(array_map('trim', [
            (string) $siteConfig->SEOSocialFacebook,
            (string) $siteConfig->SEOSocialInstagram,
            (string) $siteConfig->SEOSocialLinkedin,
            (string) $siteConfig->SEOSocialTwitter,
            (string) $siteConfig->YouTubeURL,
        ]), static fn ($v) => $v !== ''));
        if ($sameAs) {
            $node['sameAs'] = $sameAs;
        } else {
            unset($node['sameAs']);
        }

        $share = $this->imageURL($siteConfig, 'ShareImage', true);
        if ($share) {
            $node['image'] = $share;
        }
        // Deliberately omitted until the CMS holds real data: geo, openingHoursSpecification, priceRange.
        return $node;
    }

    /**
     * The phone number in the international form search engines prefer: "825-882-3881" gives
     * "+1-825-882-3881". Anything that is not a 10-digit North American number is passed through.
     */
    private function schemaPhone(SiteConfig $siteConfig): string
    {
        $phone = trim((string) $siteConfig->Phone);
        $digits = (string) preg_replace('/\D+/', '', $phone);
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            return sprintf('+1-%s-%s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
        }
        return $phone;
    }

    /** Settings > Main > Logo (syntro/silverstripe-seo's CompanyLogo) as an ImageObject, or null. */
    private function logoNode(SiteConfig $siteConfig, string $base): ?array
    {
        try {
            if (!$siteConfig->hasMethod('CompanyLogo') || !$siteConfig->CompanyLogoID) {
                return null;
            }
            $logo = $siteConfig->CompanyLogo();
            if (!$logo || !$logo->exists()) {
                return null;
            }
            $url = (string) $logo->getAbsoluteURL();
            if ($url === '') {
                return null;
            }
            $node = [
                '@type' => 'ImageObject',
                '@id' => $base . '#/schema/logo/image/',
                'url' => $url,
                'contentUrl' => $url,
                'caption' => (string) $siteConfig->Title,
            ];
            $width = (int) $logo->getWidth();
            $height = (int) $logo->getHeight();
            if ($width > 0 && $height > 0) {
                $node['width'] = $width;
                $node['height'] = $height;
            }
            return $node;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Every published service page as an Offer, so the business node names what it sells. Each Service
     * carries the same @id as the Service node on its own page.
     */
    private function offerCatalog(): ?array
    {
        try {
            $items = [];
            $name = '';
            foreach (PracticeAreaPage::get()->filter('ShowInSearch', true)->sort(['ParentID' => 'ASC', 'Sort' => 'ASC']) as $page) {
                if (!$page->canView()) {
                    continue;
                }
                $url = $page->AbsoluteLink();
                $items[] = [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        '@id' => $url . '#service',
                        'name' => (string) $page->Title,
                        'url' => $url,
                    ],
                ];
                if ($name === '' && $page->ParentID) {
                    $parent = $page->Parent();
                    $name = $parent && $parent->exists() ? trim((string) $parent->Title) : '';
                }
            }
            if (!$items) {
                return null;
            }
            return [
                '@type' => 'OfferCatalog',
                'name' => $name !== '' ? $name : 'Services',
                'itemListElement' => $items,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function webPageNode(array $node, string $language): array
    {
        $owner = $this->getOwner();
        $node['inLanguage'] = $language;
        $node['name'] = $this->getSEOPageTitle();
        $description = $this->getSEODescription();
        if ($description !== '') {
            $node['description'] = $description;
        }
        $published = $owner instanceof CustomBlogPost ? $owner->PublishDate : $owner->Created;
        $this->addIfNotEmpty($node, 'datePublished', $this->isoDate($published));
        $this->addIfNotEmpty($node, 'dateModified', $this->isoDate($owner->LastEdited));
        $image = $this->getSEOShareImage();
        if ($image) {
            $node['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $image];
        }
        return $node;
    }

    /**
     * Rebuild the breadcrumb names from plain page titles. The module uses getSEOTitle(),
     * which would put keyword-styled meta titles into the trail.
     */
    private function breadcrumbNode(array $node, string $base): array
    {
        $owner = $this->getOwner();
        $items = [];
        if (!$this->isHome()) {
            $home = SiteTree::get_by_link(RootURLController::get_homepage_link());
            $items[] = ['@type' => 'ListItem', 'name' => $home ? (string) ($home->MenuTitle ?: $home->Title) : 'Home', 'item' => $base];
        }
        if ($owner->hasMethod('getBreadcrumbItems')) {
            foreach ($owner->getBreadcrumbItems(20) as $item) {
                $items[] = [
                    '@type' => 'ListItem',
                    'name' => (string) ($item->MenuTitle ?: $item->Title),
                    'item' => $item->AbsoluteLink(),
                ];
            }
        }
        foreach ($items as $i => $item) {
            $items[$i]['position'] = $i + 1;
        }
        $node['itemListElement'] = $items;
        return $node;
    }

    private function retypeWebPage(array $nodes, ?string $type, array $extra, bool $append = false): array
    {
        foreach ($nodes as $i => $node) {
            if (!is_array($node) || !isset($node['@type'])) {
                continue;
            }
            $types = (array) $node['@type'];
            $isWebPage = (bool) array_intersect($types, ['WebPage', 'ProfilePage', 'ContactPage', 'CollectionPage', 'FAQPage']);
            if (!$isWebPage) {
                continue;
            }
            if ($type !== null) {
                if ($append) {
                    $types[] = $type;
                    $node['@type'] = array_values(array_unique($types));
                } else {
                    $node['@type'] = $type;
                }
            }
            foreach ($extra as $key => $value) {
                $node[$key] = $value;
            }
            $nodes[$i] = $node;
            break;
        }
        return $nodes;
    }

    private function personNode(PersonPage $page, string $orgId, string $url): array
    {
        $node = [
            '@type' => 'Person',
            '@id' => $url . '#person',
            'name' => (string) $page->Title,
            'url' => $url,
            'worksFor' => ['@id' => $orgId],
        ];
        $this->addIfNotEmpty($node, 'givenName', $page->FirstName);
        $this->addIfNotEmpty($node, 'familyName', $page->LastName);
        $this->addIfNotEmpty($node, 'jobTitle', $page->JobTitle);
        $this->addIfNotEmpty($node, 'telephone', $page->Phone);
        $this->addIfNotEmpty($node, 'email', $page->Email);
        $this->addIfNotEmpty($node, 'image', $this->imageURL($page, 'Headshot', false));
        $linkedIn = trim((string) $page->LinkedIn);
        if ($linkedIn !== '') {
            $node['sameAs'] = [preg_match('#^https?://#i', $linkedIn) ? $linkedIn : 'https://www.linkedin.com/in/' . $linkedIn];
        }
        try {
            $areas = [];
            foreach ($page->PracticeAreas() as $area) {
                $areas[] = (string) $area->Title;
            }
            if ($areas) {
                $node['knowsAbout'] = $areas;
            }
        } catch (\Throwable $e) {
            // relation unavailable - omit
        }
        $description = $this->plainText($page->Content, 300);
        $this->addIfNotEmpty($node, 'description', $description);
        return $node;
    }

    private function serviceNode(PracticeAreaPage $page, string $orgId, string $url): array
    {
        $node = [
            '@type' => 'Service',
            '@id' => $url . '#service',
            'name' => (string) $page->Title,
            'serviceType' => (string) $page->Title,
            'url' => $url,
            'provider' => ['@id' => $orgId],
        ];
        $areaServed = $this->areaServed();
        if ($areaServed) {
            $node['areaServed'] = $areaServed;
        }
        $description = $this->plainText($page->TeaserContent, 300);
        if ($description === '') {
            $description = trim((string) $page->MetaDescription);
        }
        $this->addIfNotEmpty($node, 'description', $description);
        return $node;
    }

    private function blogPostingNode(CustomBlogPost $page, string $orgId, string $url, ?string $webPageId, string $language): array
    {
        $node = [
            '@type' => 'BlogPosting',
            '@id' => $url . '#article',
            'headline' => (string) $page->Title,
            'url' => $url,
            'inLanguage' => $language,
            'mainEntityOfPage' => ['@id' => $webPageId ?: $url . '#webpage'],
            'publisher' => ['@id' => $orgId],
        ];
        $this->addIfNotEmpty($node, 'description', $this->plainText($page->Summary ?: $page->Content, 300));
        $this->addIfNotEmpty($node, 'datePublished', $this->isoDate($page->PublishDate));
        $this->addIfNotEmpty($node, 'dateModified', $this->isoDate($page->LastEdited));
        $this->addIfNotEmpty($node, 'image', $this->getSEOShareImage());

        $author = null;
        try {
            if ($page->hasMethod('Authors') && $page->Authors()->exists()) {
                $member = $page->Authors()->first();
                $name = $member && $member->hasMethod('getName') ? trim((string) $member->getName()) : '';
                if ($name !== '') {
                    $author = ['@type' => 'Person', 'name' => $name];
                }
            }
        } catch (\Throwable $e) {
            $author = null;
        }
        $node['author'] = $author ?: ['@id' => $orgId];
        return $node;
    }

    /**
     * Question/Answer pairs from any BucketsBlock rendered with the "Faq" style on this page.
     */
    private function faqQuestions($page): array
    {
        $questions = [];
        try {
            if (!$page->hasMethod('ElementalArea') || !$page->ElementalAreaID) {
                return [];
            }
            $blocks = BucketsBlock::get()->filter('ParentID', $page->ElementalAreaID);
            foreach ($blocks as $block) {
                $style = $block->BlockStyle();
                if (!$style || $style->TemplateName !== 'Faq') {
                    continue;
                }
                // ShownBuckets(): an FAQ still waiting for a claim (Settings > Claims) stays out of the schema too.
                foreach ($block->ShownBuckets() as $bucket) {
                    $q = trim((string) $bucket->Title);
                    $a = $this->plainText($bucket->HTML, 1000);
                    if ($q === '' || $a === '') {
                        continue;
                    }
                    $questions[] = [
                        '@type' => 'Question',
                        'name' => $q,
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
                    ];
                }
            }
        } catch (\Throwable $e) {
            return [];
        }
        return $questions;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function isHome(): bool
    {
        $owner = $this->getOwner();
        return !$owner->ParentID && $owner->URLSegment === RootURLController::get_homepage_link();
    }

    private function currentRequest()
    {
        $controller = Controller::curr();
        return $controller && $controller->hasMethod('getRequest') ? $controller->getRequest() : null;
    }

    private function isThinBlogListing(): bool
    {
        $controller = Controller::curr();
        if (!($controller instanceof BlogController)) {
            return false;
        }
        try {
            $tag = $controller->hasMethod('getCurrentTag') ? $controller->getCurrentTag() : null;
            if ($tag && $tag->exists()) {
                return true;
            }
            $year = $controller->hasMethod('getArchiveYear') ? $controller->getArchiveYear() : null;
            return !empty($year);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function areaServed(): array
    {
        return array_values(array_unique(array_filter(
            (array) Config::inst()->get(self::class, 'area_served'),
            static fn ($v) => is_string($v) && trim($v) !== ''
        )));
    }

    /**
     * HTML to a single-line plain string, cut at a sentence end where possible, else at a word.
     */
    private function plainText($html, int $max): string
    {
        // Run shortcodes first, as the page does: an unconfirmed [claim]...[/claim] sentence renders as nothing,
        // so it must not reach a meta description or the JSON-LD as raw text.
        $html = ShortcodeParser::get_active()->parse((string) $html);
        // Headings are labels, not prose (a person's name above their bio) - drop them; then keep block
        // boundaries as spaces so adjacent paragraphs never run together.
        $html = (string) preg_replace('#<h[1-6][^>]*>.*?</h[1-6]>#is', ' ', $html);
        $html = (string) preg_replace('#</(p|div|li|br|tr|blockquote)>|<br\s*/?>#i', ' ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if ($text === '' || mb_strlen($text) <= $max) {
            return $text;
        }
        $window = mb_substr($text, 0, $max);
        $sentenceEnd = max((int) mb_strrpos($window, '. '), (int) mb_strrpos($window, '? '), (int) mb_strrpos($window, '! '));
        if ($sentenceEnd >= min(100, (int) ($max * 0.6))) {
            return trim(mb_substr($window, 0, $sentenceEnd + 1));
        }
        $cut = (string) preg_replace('/\s+\S*$/u', '', $window);
        return rtrim($cut, " ,;:-") . '...';
    }

    private function isoDate($raw): ?string
    {
        if (empty($raw)) {
            return null;
        }
        $timestamp = strtotime((string) $raw);
        return $timestamp === false ? null : date('c', $timestamp);
    }

    private function addIfNotEmpty(array &$node, string $key, $value): void
    {
        if ($value === null) {
            return;
        }
        if (is_string($value) && trim($value) === '') {
            return;
        }
        $node[$key] = $value;
    }
}
