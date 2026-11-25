<?php
namespace App\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\TextField;

class BlogCategoryExtension extends Extension
{

	private static $default_sort = 'Sort ASC';

	private static $db = [
        'Sort' => 'Int'
    ];
		
    private static $has_one = [
        'Image' => Image::class,
    ];
    private static $owns = [
        'Image',
    ];

    public function updateCMSFields( FieldList $fields )
    {
    	$fields->addFieldToTab('Root.Main',Textfield::create('Sort'));

        $f = UploadField::create('Image','Category Image');
        $f->setFolderName('Blog/CategoryImages');
        $f->setDescription('Optional Background Image');
        $f->setAllowedExtensions(['jpg','jpeg','png','webp']);
        $fields->addFieldToTab('Root.Main', $f);
        
    }

}
