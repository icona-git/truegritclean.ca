<?php
namespace App\Models;

use Override;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Versioned\Versioned;
use DNADesign\Elemental\Forms\TextCheckboxGroupField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use App\Elements\BucketsBlock;

class Bucket extends DataObject
{
    private static $default_sort = 'Sort';
    private static $singular_name = 'Bucket';
    private static $plural_name = 'Buckets';
    private static $table_name = 'Bucket';
    
    private static $versioned_gridfield_extensions = true;
    private static $extensions = [
        Versioned::class
    ];

    private static $db = [
        'Title' => 'Varchar(255)',
        'ShowTitle' => 'Boolean',
        'SubTitle' => 'Varchar(255)',
        'HTML' => 'HTMLText',
        'Sort' => 'Int',
        // Claim keys (App\Models\Claims) this item waits for, as a JSON array. Empty: always shown.
        'Claims' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Image' => Image::class,
        'ParentBlock' => BucketsBlock::class,
        'Link' => Link::class,
        'SecondaryFile' => File::class,
    ];
    private static $owns = [
        'Image', 'SecondaryFile', 'Link'
    ];

    private static $summary_fields = [
        'Preview' => 'Preview',
        'Title'
    ];

    private static $indexes = [
    ];

    #[Override]
    public function getCMSFields()
    {

        $fields = parent::getCMSFields();
        $fields->removeByName(['ParentBlockID','Sort','Link']);

        $fields->removeByName('ShowTitle');
        $fields->replaceField(
            'Title',
            TextCheckboxGroupField::create()
                ->setName('Title')
        );
        $fields->addFieldToTab('Root.Main', TextField::create('SubTitle')->setDescription('Optional text used in special formatting for a bucket.'));
        $fields->addFieldToTab('Root.Main', HTMLEditorField::create('HTML'));
        $fields->addFieldToTab('Root.Main', LinkField::create('Link', 'Call to Action Link'));

        $fields->addFieldToTab('Root.Main', CheckboxSetField::create('Claims', 'Show only when confirmed', Claims::options())
            ->setDescription('Leave all unticked for an item that always shows. Tick the claims this item states: it stays off the site until every ticked claim is confirmed in Settings > Claims.'));

        $f = UploadField::create('Image', 'Image');
        $f->setFolderName('Buckets');
        $f->setAllowedExtensions(['jpg','jpeg','png','webp']);
        $fields->addFieldToTab('Root.Main',$f);


        $f = UploadField::create('SecondaryFile','Secondary File');
        $f->setFolderName('Buckets');
        $f->setDescription('Optionally add a secondary Image or File');
        $f->setAllowedExtensions(['jpg','jpeg','png','webp']);
        $fields->addFieldToTab('Root.Main',$f);

        // Rename the "Main" tab
        $fields->fieldByName('Root.Main')
            ->setTitle(_t(self::class . '.MainTabLabel', 'Content'));

        return $fields;

    }

    /** False while any claim this item ticks is unconfirmed in Settings > Claims (App\Models\Claims). */
    public function IsShown(): bool
    {
        return Claims::allConfirmed(Claims::decode($this->Claims));
    }

    public function getPreview()
    {
        if ($this->Image() && $this->Image()->exists()) {
            if ($this->Image()->getIsImage()) {
                $rv = DBHTMLText::create('ImgPreview');
                $rv->setValue('<img src="'.$this->Image()->FitMax(150,75)->getURL().'" class="img-fluid" />');
                return $rv ;
            } else {
                return '';
            }
        }
        return '';
    }



}
