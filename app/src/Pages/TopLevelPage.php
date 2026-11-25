<?php
namespace App\Pages;

use Override;
use Page;

class TopLevelPage extends Page
{
    private static $table_name = 'TopLevelPage';

    private static $cms_icon_class = 'font-icon-page-multiple';
    private static $class_description = 'Top Level Page';

    private static $db = [
    ];

    #[Override]
    public function getCMSFields()
    {
        // if (!$fields->dataFieldByName('Content')) {
        //     $fields->addFieldToTab('Root.Main', HTMLEditorField::create('Content'), 'ElementalArea');
        // }

        return parent::getCMSFields();
    }
}
