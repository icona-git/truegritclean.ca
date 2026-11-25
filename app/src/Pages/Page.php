<?php

namespace {

    use SilverStripe\Forms\CompositeField;
    use SilverStripe\CMS\Controllers\RootURLController;
    use SilverStripe\CMS\Model\SiteTree;
    use SilverStripe\Assets\Image;
    use SilverStripe\Assets\Storage\AssetContainer;
    use SilverStripe\Forms\TextareaField;
    use SilverStripe\Forms\CheckboxField;
    use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
    use SilverStripe\AssetAdmin\Forms\UploadField;

    class Page extends SiteTree
    {
        private static $table_name = 'Page';

        private static $db = [
            'ShowInTopMenu' => 'Boolean',
            'ShowInFooterMenu' => 'Boolean',
            'ShowTopNavSubMenu' => 'Boolean',
            'ShowInTopUtilityMenu' => 'Boolean',
            'ShowInFooterUtilityMenu' => 'Boolean',
            'HeroLayout' => 'Varchar(50)',
            'HeroLabel' => 'Varchar(150)',
            'HeroTitle' => 'Varchar(255)',
            'HeroContent' => 'HTMLText',
            'HeroFocusX' => "Enum('Left,Center,Right','Center')",
            'TeaserContent' => 'HTMLText',
            'SchemaJson' => 'Text',
            'HideRailNav' => 'Boolean',
        ];

        private static $has_one = [
            'HeroImage'       => Image::class,
            'HeroImageMobile' => Image::class,
            'TeaserImage'     => Image::class,
        ];
        private static $owns = [
            'HeroImage',
            'HeroImageMobile',
            'TeaserImage',
        ];
        private static $cascade_duplicates = [
        ];

        #[Override]
        public function getCMSFields()
        {
            $fields = parent::getCMSFields();

            $fields->addFieldToTab('Root.Hero', \SilverStripe\Forms\DropdownField::create(
                'HeroLayout',
                'Hero Layout',
                [
                    ''             => 'Default — headline only',
                    'bleed-intro'  => 'Bleed Intro — hero + white intro section with logo cutout',
                ]
            )->setDescription('Bleed Intro extends the hero image behind an intro content block below. Requires HeroContent to be set.'));

            $fields->addFieldToTab('Root.Hero', \SilverStripe\Forms\TextField::create(
                'HeroLabel',
                'Hero Label (gold text)'
            )->setDescription('Short label above the headline — e.g. "About" or "Criminal Defence". Defaults to parent page name if left empty.'));

            $fields->addFieldToTab('Root.Hero', \SilverStripe\Forms\TextField::create(
                'HeroTitle',
                'Hero Title'
            )->setDescription('Override the hero headline. Defaults to the page title if left empty.'));

            $f = UploadField::create('HeroImage');
            $f->setFolderName('Hero');
            $f->setDescription('Top Hero Banner');
            $fields->addFieldToTab('Root.Hero', $f);

            $f = UploadField::create('HeroImageMobile');
            $f->setFolderName('Hero');
            $f->setDescription('Optional mobile-specific hero image (used below 992px). Falls back to a cropped version of the main image if left empty.');
            $fields->addFieldToTab('Root.Hero', $f);

            $fields->addFieldToTab('Root.Hero', \SilverStripe\Forms\DropdownField::create(
                'HeroFocusX',
                'Hero image focus',
                [
                    'Left'   => 'Left side',
                    'Center' => 'Centre',
                    'Right'  => 'Right side',
                ]
            )->setDescription('Narrow screens crop the sides of the hero photo; this chooses which side stays in view. Text must never cover a face: after changing the photo or this setting, check the page on a phone. A page without its own hero image uses its parent\'s image and this setting from the parent.'));

            $fields->addFieldToTab('Root.Hero', HTMLEditorField::create('HeroContent'));

            $f = UploadField::create('TeaserImage');
            $f->setFolderName('Teasers');
            $f->setDescription('Thumbnail shown when this page appears in a list or card grid.');
            $fields->addFieldToTab('Root.Teaser', $f);
            $fields->addFieldToTab('Root.Teaser', HTMLEditorField::create('TeaserContent', 'Teaser Text')->setDescription('Short description for card grids and listing pages.'));

            $fields->addFieldToTab('Root.Main', HTMLEditorField::create('Content'),'ElementalArea');

            $fields->addFieldToTab('Root.SEO.SEORoot.Schema', TextAreaField::create('SchemaJson')->setRows(25));            
            return $fields;
        }

        #[Override]
        public function getSettingsFields()
        {
            $fields = parent::getSettingsFields();
            $settingsTab = $fields->findOrMakeTab('Root.Settings');
            $visibility = $settingsTab->getChildren()->fieldByName('Visibility');
            if ($visibility && $visibility instanceof CompositeField) {
                $visibility->push(CheckboxField::create('ShowInTopMenu','Show in Main Menu')
                    ->setDescription('Top-level pages only: adds this page to the header menu.'));
                $visibility->push(CheckboxField::create('ShowTopNavSubMenu','Show Main SubMenu'));
                $visibility->push(CheckboxField::create('ShowInTopUtilityMenu','Show in Top Utility Menu'));
                $visibility->push(CheckboxField::create('ShowInFooterMenu','Show in Footer Menu')
                    ->setDescription('Top-level pages only: adds this page to the footer menu, whether or not it is in the main menu.'));
                $visibility->push(CheckboxField::create('ShowInFooterUtilityMenu','Show in Footer Utility Menu'));
                $visibility->push(CheckboxField::create('HideRailNav', 'Hide Rail Navigation'));
            }
            return $fields;
        }

        private static array $scaffold_cms_fields_settings = [
            'ignoreFields' => [
                'ShowInTopMenu',
                'ShowTopNavSubMenu',
                'ShowInTopUtilityMenu',
                'ShowInFooterMenu',
                'ShowInFooterUtilityMenu',
                'HideRailNav'
            ]
        ];

        public function HeroImageOrDefault() {
            if(!$this->HeroImageID){
                if (!$this->ParentID) {
                    return;
                } // Avoid errors for top level pages with no image.
                return $this->Parent()->HeroImageOrDefault();
            }else{
                return $this->HeroImage();
            }
        }

        /**
         * The side of the hero photo that stays in view when a narrow screen crops it: 'left', 'center' or
         * 'right', from HeroFocusX. It follows the image: a page showing its parent's hero image
         * (HeroImageOrDefault()) uses the parent's setting. The hero templates use it as the
         * hero-focus--* class (_hero.scss) and, through HeroImageFill(), for the crops served below 992px.
         */
        public function HeroFocusPosition(): string
        {
            if (!$this->HeroImageID && $this->ParentID) {
                $parent = $this->Parent();
                if ($parent instanceof Page) {
                    return $parent->HeroFocusPosition();
                }
            }
            $focus = strtolower((string) $this->HeroFocusX);
            return in_array($focus, ['left', 'right'], true) ? $focus : 'center';
        }

        /**
         * The hero image (HeroImageOrDefault()) cropped to $width x $height, keeping the side
         * HeroFocusPosition() names. Replaces $HeroImageOrDefault.Fill() in the hero templates, which always
         * kept the centre.
         */
        public function HeroImageFill($width, $height): ?AssetContainer
        {
            $image = $this->HeroImageOrDefault();
            if (!$image || !$image->exists()) {
                return null;
            }
            return $image->AnchorFill($width, $height, $this->HeroFocusPosition());
        }

        /**
         * The home page, which heads the breadcrumb trail (BreadcrumbsTemplate.ss). It is the same record the
         * BreadcrumbList JSON-LD starts with (App\Extensions\SEOMetaExtension), so the visible trail and the
         * schema give it the same name: its menu title, e.g. "Accueil" on a French site.
         */
        public function BreadcrumbHome(): ?SiteTree
        {
            return SiteTree::get_by_link(RootURLController::get_homepage_link());
        }

        // YouTube ShortCode handler
        public static function YouTubeShortCode($arguments, $content = null, $parser = null, $tagName = null)
        {
            $html = '';
            if( !empty($arguments['id']) ){
                $html .= '<div class="video-wrapper">';
                $html .= '<iframe id="ytplayer" src="https://www.youtube.com/embed/'.$arguments['id'].'?theme=light&showinfo=0&rel=0&autohide=1&modestbranding=1&autoplay=0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" frameborder="0" allowfullscreen></iframe>';
                $html .= '</div>';
            }

            return $html;
        }

    }
}
