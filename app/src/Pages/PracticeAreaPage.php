<?php
namespace App\Pages;

use Override;
use Page;

class PracticeAreaPage extends Page
{
    private static $table_name = 'PracticeAreaPage';

    private static $cms_icon_class = 'font-icon-p-book';
    // This site sells services, not practice areas: the CMS calls this page type "Service page".
    private static $singular_name = 'Service page';
    private static $plural_name = 'Service pages';
    private static $class_description = 'A service the company offers. Put it under Services; it is listed there and in the footer.';

    private static $db = [];

    /** Lawyers who list this practice area (PersonPage > Areas of Practice). */
    private static $belongs_many_many = [
        'Lawyers' => PersonPage::class . '.PracticeAreas',
    ];

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }
}
