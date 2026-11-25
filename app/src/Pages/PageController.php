<?php
namespace {

    use SilverStripe\CMS\Controllers\ContentController;
    use SilverStripe\CMS\Model\SiteTree;
    use SilverStripe\Model\ArrayData;
    use SilverStripe\Model\List\ArrayList;
    use SilverStripe\SiteConfig\SiteConfig;
    use SilverStripe\View\Requirements;
    use App\Pages\PracticeAreaPage;
    use App\Pages\TopLevelPage;

    class PageController extends ContentController
    {
        private static $allowed_actions = [];

        #[Override]
        protected function init()
        {
            parent::init();
            Requirements::css('themes/app-theme/css/dist/site.css');
            Requirements::javascript('themes/app-theme/js/dist/site-min.js', ['defer' => true]);
        }

        public function MainMenu(){
            return $this->getMenu(1)->filter(["ShowInTopMenu" => 1]);
        }

        /**
         * The footer's "Firm" column: top-level pages ticked "Show in Footer Menu", in site-tree order. Built
         * like MainMenu(), so it also honours "Show in menus?" and hides what the visitor cannot view, but
         * the two flags are independent: a contact page that the header reaches another way still belongs here.
         */
        public function FooterMenu(){
            return $this->getMenu(1)->filter(["ShowInFooterMenu" => 1]);
        }
        
        public function FooterUtilityMenu(){
            return Page::get()->filter(["ShowInFooterUtilityMenu" => 1]);
        }
        
        public function TopUtilityMenu(){
            return Page::get()->filter(["ShowInTopUtilityMenu" => 1]);
        }
        
        public function FooterSectionLinks(){
            return TopLevelPage::get();
        }

        /** All practice-area pages in CMS order - the footer link hub. */
        public function FooterPracticeAreas()
        {
            return PracticeAreaPage::get()->sort('Sort ASC');
        }

        /**
         * Look a page up by its URL segment / nested path for guarded template links,
         * e.g. <% if $PageByURL('contact') %>. Returns null when the page does not exist.
         */
        public function PageByURL($link)
        {
            $link = trim((string) $link, '/');
            if ($link === '') {
                return null;
            }
            return SiteTree::get_by_link($link);
        }

        /**
         * Social profiles configured in Settings (SEO > Social + YouTube). Empty list when none.
         * Each item: Name, URL, Icon (filename under themes/app-theme/images/).
         */
        public function SocialLinks()
        {
            $siteConfig = SiteConfig::current_site_config();
            $map = [
                ['field' => 'SEOSocialFacebook',  'name' => 'Facebook',  'icon' => 'icon-facebook.svg'],
                ['field' => 'SEOSocialTwitter',   'name' => 'X',         'icon' => 'icon-twitter.svg'],
                ['field' => 'SEOSocialLinkedin',  'name' => 'LinkedIn',  'icon' => 'icon-linkedin.svg'],
                ['field' => 'SEOSocialInstagram', 'name' => 'Instagram', 'icon' => 'icon-instagram.svg'],
                ['field' => 'YouTubeURL',         'name' => 'YouTube',   'icon' => 'icon-youtube.svg'],
            ];
            $list = ArrayList::create();
            foreach ($map as $item) {
                $url = trim((string) $siteConfig->{$item['field']});
                if ($url === '' || !preg_match('#^https?://#i', $url)) {
                    continue;
                }
                $list->push(ArrayData::create(['Name' => $item['name'], 'URL' => $url, 'Icon' => $item['icon']]));
            }
            return $list;
        }
    }
}
