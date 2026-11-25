<?php
namespace App\Pages;

use Override;
use Page;
use App\Elements\BucketsBlock;
use SilverStripe\ORM\FieldType\DBHTMLText;

class HomePage extends Page
{
    private static $table_name = 'HomePage';
    private static $cms_icon_class = 'font-icon-p-home';
    private static $class_description = 'Home Page of the Site';


    #[Override]
    public function getCMSFields()
    {
        return parent::getCMSFields();
    }
}
