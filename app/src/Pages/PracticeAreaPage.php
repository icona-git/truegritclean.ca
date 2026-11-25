<?php
namespace App\Pages;

use Override;
use Page;

class PracticeAreaPage extends Page
{
    private static $table_name = 'PracticeAreaPage';

    private static $cms_icon_class = 'font-icon-p-book';
    private static $class_description = 'Use this page type to create new practice areas in the site';

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
