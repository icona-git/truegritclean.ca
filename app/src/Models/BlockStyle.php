<?php

namespace App\Models;

use Override;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\AssetAdmin\Forms\UploadField;
use DNADesign\Elemental\Models\BaseElement;


class BlockStyle extends DataObject
{
    private static $table_name = 'BlockStyle';

    private static $db = [
        'Title' => 'Varchar(255)',
        'BlockType' => 'Varchar(255)',
        'TemplateName' => 'Varchar(255)',
    ];

    private static $has_one = [
        'File' => File::class,
    ];

    private static $summary_fields = [
        'Preview' => 'Preview',
        'Title',
        'BlockType',
        'TemplateName'
    ];

    private static $indexes = [
        'BlockType' => true,
    ];
    
    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $types = $this->getAllBlockTypes();
        $f = DropdownField::create('BlockType','Block Type',$types);
        $f->setDescription('What type of block does this style apply to?');
        $fields->addFieldToTab('Root.Main',$f);

        $fields->addFieldToTab('Root.Main',TextField::create('TemplateName'));

        $f = UploadField::create('File','Thumbnail/Preview');
        $f->setFolderName('BlockStyleThumbnails');
        $fields->addFieldToTab('Root.Main',$f);

        return $fields;

    }

    public function getPreview()
    {
        if ($this->File() && $this->File()->exists()) {
            if ($this->File()->getIsImage()) {
                $rv = DBHTMLText::create('ImgPreview');
                $rv->setValue('<img src="'.$this->File()->FitMax(700,400)->getURL().'" class="img-fluid" />');
                return $rv ;
            } else {
                return '';
            }
        }
        return '';
    }

    // A static method for us to get Block types into a list in BlockStyle Admin (where, we are not attached to a page/controller)
    // Copying parts of /vendor/dnadesign/silverstripe-elemental/src/Extensions/ElementalAreasExtension.php : 65: getElementalTypes()
    public function getAllBlockTypes()
    {
        $availableClasses = ClassInfo::subclassesFor(BaseElement::class);
        $list = [];

        foreach ($availableClasses as $availableClass) {
            $inst = singleton($availableClass);
            $list[$availableClass] = $inst->getType();
        }
        asort($list);

        if (isset($list[BaseElement::class])) {
            unset($list[BaseElement::class]);
        }

        return $list;
    }



}
