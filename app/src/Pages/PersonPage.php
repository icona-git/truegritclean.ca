<?php
namespace App\Pages;

use Override;
use Page;
use SilverStripe\Assets\File;
use SilverStripe\Forms\TextField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Versioned\GridFieldArchiveAction;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use App\Elements\PersonBlock;
use App\Pages\PracticeAreaPage;

class PersonPage extends Page
{
    private static $cms_icon_class = 'font-icon-p-profile';
    private static $class_description = 'A Person/Profile Page';
    private static $table_name = 'PersonPage';
    
    private static $db = [
        'FirstName' => 'Varchar(255)',
        'LastName' => 'Varchar(255)',
        'JobTitle' => 'Varchar(255)',
        'Phone' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'LinkedIn' => 'Varchar(255)',
        'RailContent' => 'HTMLText',
    ];

    private static $has_one = [
        'Headshot' => File::class,
    ];
    private static $many_many = [
        'LegalAssistants' => PersonPage::class,
        'PracticeAreas' => PracticeAreaPage::class,
    ];
    private static $many_many_extraFields = [
        'LegalAssistants' => [
          'ManySort' => 'Int'
        ],
        'PracticeAreas' => [
          'ManySort' => 'Int'
        ],
    ];
    private static $owns = [
        'Headshot'
    ];

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('PersonBlocks');
        $fields->removeByName('RailContent');

        $teaserField = $fields->dataFieldByName('TeaserImage');
        if ($teaserField) {
            $teaserField->setFolderName('People');
        }

        //$fields->removeFieldFromTab('Root', 'Content');
        $f = UploadField::create('Headshot');
        $f->setFolderName('People');
        //$f->setDescription('');
        $f->setAllowedExtensions(['jpg','png']);
        $fields->addFieldToTab('Root.Details',$f);

        $fields->addFieldToTab('Root.Details', TextField::create('FirstName'));
        $fields->addFieldToTab('Root.Details', TextField::create('LastName'));
        $fields->addFieldToTab('Root.Details', TextField::create('JobTitle'));
        $fields->addFieldToTab('Root.Details', TextField::create('Phone'));
        $fields->addFieldToTab('Root.Details', TextField::create('Email'));
        $fields->addFieldToTab('Root.Details', TextField::create('LinkedIn', 'LinkedIn Handle (e.g. jsmith)'));
        $fields->addFieldToTab('Root.Details', HTMLEditorField::create('RailContent')->setRows(20));

        $src = $this->LegalAssistants()->sort('ManySort');
        $config = GridFieldConfig_RelationEditor::create();
        $config->addComponent(GridFieldOrderableRows::create('ManySort'));
        $config->removeComponentsByType(GridFieldAddNewButton::class);
        $config->removeComponentsByType(GridFieldEditButton::class);
        $config->removeComponentsByType(GridFieldFilterHeader::class);
        $config->removeComponentsByType(GridFieldDeleteAction::class);
        $config->removeComponentsByType(GridFieldArchiveAction::class);
        $config->addComponent(new GridFieldDeleteAction(true));
        $f = GridField::create(
            'LegalAssistants',
            'Legal Assistants',
            $src,
            $config
        );
        $fields->addFieldToTab('Root.Assistants', $f);

        $paConfig = GridFieldConfig_RelationEditor::create();
        $paConfig->addComponent(GridFieldOrderableRows::create('ManySort'));
        $paConfig->removeComponentsByType(GridFieldFilterHeader::class);
        $paConfig->removeComponentsByType(GridFieldDeleteAction::class);
        $paConfig->removeComponentsByType(GridFieldArchiveAction::class);
        $paConfig->addComponent(new GridFieldDeleteAction(true));
        $f = GridField::create(
            'PracticeAreas',
            'Areas of Practice',
            $this->PracticeAreas()->sort('ManySort'),
            $paConfig
        );
        $fields->addFieldToTab('Root.PracticeAreas', $f);

        if (!$fields->dataFieldByName('Content')) {
            $fields->addFieldToTab('Root.Main', HTMLEditorField::create('Content'), 'ElementalArea');
        }

        

        return $fields;
    }

    private static $belongs_many_many = [
        "PersonBlocks" => PersonBlock::class,
    ];
}
