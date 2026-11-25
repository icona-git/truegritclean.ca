<?php
namespace App\Elements;

use Override;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\NumericField;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\ORM\FieldType\DBField;
use App\Pages\CustomBlogPost;
use App\Pages\PracticeAreaPage;

class SpecialListBlock extends BaseElement
{
    private static $singular_name = 'Special List Block';
    private static $plural_name = 'Special List Blocks';
    private static $icon = 'font-icon-block-file-list';
    private static $table_name = 'SpecialListBlock';
    private static $inline_editable = false;

    private static $db = [
        'HTML' => 'HTMLText',
        'NumItems' => 'Int'
    ];

    private static $has_one = [
        'CtaLink' => Link::class,
        'BgImage' => Image::class,
    ];

    private static $owns = [
        'BgImage'
    ];

    #[Override]
    public function getType()
    {
        return 'Special List';
    }

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', HtmlEditorField::create('HTML'));
        $fields->addFieldToTab('Root.Main', LinkField::create('CtaLink', 'Optional Call to Action Link'));

        $fields->addFieldToTab('Root.Main', NumericField::create('NumItems', 'Number of Items')->setDescription('Optionally limit the number of items to display (leave as "0" for all)'));

        $f = UploadField::create('BgImage','Image');
        $f->setFolderName('Blocks/Images');
        $f->setDescription('Optional Image');
        $fields->addFieldToTab('Root.Main',$f);

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

    public function getBlogPosts(){
        $records = CustomBlogPost::get();
        if($n = $this->NumItems){
            $records = $records->limit($n,0);
        }
        return $records;
    }

    public function getPracticeAreas(){
        $records = PracticeAreaPage::get()->sort('Sort ASC');
        if($n = $this->NumItems){
            $records = $records->limit($n,0);
        }
        return $records;
    }

}
