<?php
namespace App\Pages;

use Override;
use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Assets\File;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;

class CustomBlogPost extends BlogPost
{
    private static $db = [
    ];
    
    private static $class_description = 'Blog Post';
    private static $plural_name = 'Blog Posts';
    private static $singular_name = 'Blog Post';
    private static $table_name = 'CustomBlogPost';


    private static $has_one = [
        'DownloadableFile' => File::class,
        'ExternalLink' => Link::class,
    ];
    private static $owns = [
        'DownloadableFile'
    ];

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();


     $fields = parent::getCMSFields();
        $fields->removeByName(['Hero', 'ElementalArea']);

        // Don't clutter the admin for blog posts
        $fields->removeFieldFromTab('Root.Main', 'ExternalLink');
        // $fields->removeFieldFromTab('Root', 'ElementalArea');

        // Add Back the Content Field
        $fields->addFieldToTab('Root.Main', HtmlEditorField::create('Content'),'CustomSummary');

        if (!$fields->dataFieldByName('Content')) {
            $fields->addFieldToTab('Root.Main', HTMLEditorField::create('Content'), 'Metadata');
        }

        // Set Folder name and desc for Img
        $fields->dataFieldByName( 'FeaturedImage' )
            ->setFolderName( 'Blog' )
            ->setDescription('Recommended minimum size: 700 x 400');

        $fields->addFieldToTab('Root.Main', LinkField::create('ExternalLink', 'Link to External Site or Source'),'Content');

        $f = UploadField::create('DownloadableFile','Optional PDF or File for Download');
        $f->setFolderName('Blog');
        $fields->addFieldToTab('Root.Main',$f,'Content');


        return $fields;
    }

    public function HeroContent()
    {
        return $this->Parent()->HeroContent;
    }

    // Next / Prev Navigation (like wordpress)
    /**
     * @return DataObject
     */
    public function getNextPost()
    {
        return CustomBlogPost::get()
            ->filter([
                'ParentID' => $this->owner->ParentID,
                'PublishDate:GreaterThanOrEqual' => $this->owner->PublishDate
            ])
            ->exclude(['ID' => $this->owner->ID])->sort(['PublishDate' => 'ASC'])
            ->first();
    }

    /**
     * @return DataObject
     */
    public function getPreviousPost()
    {
        return CustomBlogPost::get()
            ->filter([
                'ParentID' => $this->owner->ParentID,
                'PublishDate:LessThanOrEqual' => $this->owner->PublishDate
            ])
            ->exclude(['ID' => $this->owner->ID])->sort(['PublishDate' => 'DESC'])
            ->first();
    }
}
