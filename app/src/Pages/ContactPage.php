<?php
namespace App\Pages;

use Override;
use SilverStripe\UserForms\Model\UserDefinedForm;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class ContactPage extends UserDefinedForm
{   
    private static $cms_icon_class = 'font-icon-p-mail';
    private static $class_description = 'The Contact Page';
    private static $table_name = 'ContactPage';

    private static $db = [
    ];
    private static $has_one = [];

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        if (!$fields->dataFieldByName('Content')) {
            $fields->addFieldToTab('Root.Main', HTMLEditorField::create('Content'), 'ElementalArea');
        }

        return $fields;
    }

}
