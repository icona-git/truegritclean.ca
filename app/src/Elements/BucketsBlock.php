<?php
namespace App\Elements;

use Override;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Assets\File;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\FieldType\DBField;
use App\Models\Bucket;

class BucketsBlock extends BaseElement
{
    private static $inline_editable = false;
    private static $singular_name = 'Buckets Block';
    private static $plural_name = 'Buckets Blocks';
    private static $icon = 'font-icon-block-layout';
    private static $table_name = 'BucketsBlock';

    private static $db = [
        'HTML' => 'HTMLText',
    ];

    private static $has_one = [
        'BgImage' => File::class,
        'CtaLink' => Link::class,
    ];

    private static $has_many = [
        'Buckets' => Bucket::class,
    ];

    private static $owns = [
        'BgImage', 'Buckets'
    ];

    #[Override]
    public function getType()
    {
        return 'Buckets';
    }

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(['Buckets','CtaLink']);

        $tab = Tab::create('Buckets');
        // Buckets Grid
        $gridFieldConfig = GridFieldConfig_RelationEditor::create();
        $gridFieldConfig->addComponent(GridFieldOrderableRows::create('Sort'));
        $gridFieldConfig->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
        $grid = GridField::create('Buckets', 'Buckets', $this->Buckets(), $gridFieldConfig);
        $tab->push($grid);
        $fields->insertBefore('Main', $tab);


         // Main Content Fields
        $fields->addFieldToTab('Root.Main', HTMLEditorField::create('HTML'));
        $fields->addFieldToTab('Root.Main', LinkField::create('CtaLink', 'Call to Action Link'));

        $f = UploadField::create('BgImage','Background Image');
        $f->setFolderName('Blocks/Images');
        $f->setDescription('Optional Background Image');
        $f->setAllowedExtensions(['jpg','jpeg','png','webp']);
        $fields->addFieldToTab('Root.Main',$f);



        $fields->fieldByName('Root.Main')
            ->setTitle(_t(self::class . '.MainTabLabel', 'Content'));

        return $fields;
    }

    /**
     * The buckets visitors may see: Buckets() without the items still waiting for a claim
     * (App\Models\Bucket::IsShown()). Templates and the FAQ schema loop this, never Buckets().
     */
    public function ShownBuckets(): SS_List
    {
        return $this->Buckets()->sort('Sort')->filterByCallback(fn (Bucket $bucket) => $bucket->IsShown());
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
