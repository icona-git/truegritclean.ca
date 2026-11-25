<?php
namespace App\Pages;

use Override;
use SilverStripe\Blog\Model\Blog;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class CustomBlog extends Blog
{
    private static $db = [
        'PostsListingTitle' => 'Varchar(255)',
        'Disclaimer' => 'HTMLText',
    ];
    private static $allowed_children = [
        CustomBlogPost::class
    ];

    private static $class_description = 'Enhanced Blog';
    private static $plural_name = 'Blogs';
    private static $singular_name = 'Blog';
    private static $table_name = 'CustomBlog';

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Add Sortable to Blog Categories!
        $categoryGrid = $fields->fieldByName('Root.Categorisation.Categories');
        $categoryGridConf = $categoryGrid->getConfig();
        $categoryGridConf->addComponent(GridFieldOrderableRows::create());

        $fields->addFieldToTab('Root.Main', TextField::create('PostsListingTitle')->setDescription('Optional text to describe the list of upcoming publications or posts'),'Content');
        $fields->addFieldToTab('Root.Disclaimer', HTMLEditorField::create('Disclaimer')->setRows(10));

        return $fields;
    }

}
