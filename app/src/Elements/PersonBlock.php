<?php
namespace App\Elements;

use Override;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Versioned\GridFieldArchiveAction;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\Tab;
use App\Pages\PersonPage;

class PersonBlock extends BaseElement
{
    private static $singular_name = 'Person Block';
    private static $plural_name = 'Person Blocks';
    private static $icon = 'font-icon-block-users';
    private static $table_name = 'PersonBlock';
    private static $inline_editable = false;

    private static $db = [
        'HTML' => 'HTMLText',
    ];
    private static $many_many = [
        'People' => PersonPage::class
    ];
    private static $many_many_extraFields = [
        'People' => [
          'ManySort' => 'Int'
        ]
    ];
    private static $has_one = [
        'CtaLink' => Link::class,
    ];
    private static $owns = [
        'People'
    ];

    #[Override]
    public function getType()
    {
        return 'Person Block';
    }

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['People']);
        $fields->addFieldToTab('Root.Main', HTMLEditorField::create('HTML')->setRows(20));
        $fields->addFieldToTab('Root.Main', LinkField::create('CtaLink', 'Optional Call to Action Link'));

        // Person Selection via GridField
        $myTab = Tab::create('People', 'Persons');
        $fields->insertBefore('Main', $myTab);

        $src = $this->People()->sort('ManySort');
        $config = GridFieldConfig_RelationEditor::create();
        $config->addComponent(GridFieldOrderableRows::create('ManySort'));
        $config->removeComponentsByType(GridFieldAddNewButton::class);
        $config->removeComponentsByType(GridFieldEditButton::class);
        $config->removeComponentsByType(GridFieldFilterHeader::class);
        $config->removeComponentsByType(GridFieldDeleteAction::class);
        $config->removeComponentsByType(GridFieldArchiveAction::class);
        $config->addComponent(new GridFieldDeleteAction(true));
        $f = GridField::create(
            'People',
            'People',
            $src,
            $config
        );
        $fields->addFieldToTab('Root.People', $f);

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
