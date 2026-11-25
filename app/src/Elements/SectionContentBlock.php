<?php
namespace App\Elements;

use Override;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;

class SectionContentBlock extends BaseElement
{
    private static $singular_name = 'Section Content Block';
    private static $plural_name = 'Section Content Blocks';
    private static $icon = 'font-icon-block-banner';
    private static $table_name = 'SectionContentBlock';
    private static $inline_editable = false;

    private static $db = [
        'HTML' => 'HTMLText',
        'SubTitle' => 'Varchar(255)'
    ];

    private static $has_one = [
        'BgImage' => Image::class,
        'BgImageMobile' => Image::class,
        'CtaLink' => Link::class,
    ];

    private static $has_many = [
    ];

    private static $owns = [
        'BgImage',
        'BgImageMobile',
    ];

   

    #[Override]
    public function getType()
    {
        return 'Section Content';
    }

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(['CtaLink','SubTitle','SubTitleTag']);

        // Main Content Fields
        // $fields->addFieldToTab('Root.Main',TextField::create('SubTitle'));
        $fields->addFieldToTab('Root.Main', HtmlEditorField::create('HTML'));
        $fields->addFieldToTab('Root.Main', LinkField::create('CtaLink', 'Optional Call to Action Link'));

        $f = UploadField::create('BgImage', 'Background Image');
        $f->setFolderName('Blocks/Images');
        $f->setDescription('Desktop background image');
        $fields->addFieldToTab('Root.Main', $f);

        $fMobile = UploadField::create('BgImageMobile', 'Mobile Background Image');
        $fMobile->setFolderName('Blocks/Images');
        $fMobile->setDescription('Optional — if not set, desktop image will be cropped for mobile');
        $fields->addFieldToTab('Root.Main', $fMobile);

        $fields->fieldByName('Root.Main')
            ->setTitle(_t(self::class . '.MainTabLabel', 'Content'));

        return $fields;
    }

    #[Override]
    protected function provideBlockSchema()
    {
        $blockSchema = parent::provideBlockSchema();
        $blockSchema['content'] = $this->getSummary();
        if($blockstyleimg = $this->BlockStyle()->File()){
            $blockSchema['fileURL'] = $blockstyleimg->URL;
            $blockSchema['fileTitle'] = '';
        }
        return $blockSchema;
    }
    #[Override]
    public function getSummary()
    {
        $html ='<p>&nbsp;</p>';
        return DBField::create_field('HTMLText', $html)->ContextSummary();
    }



}
