<?php
namespace App\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Forms\UploadField;

class MySiteConfig extends Extension
{
    private static $db = [
        'StreetAddress' => 'Varchar(255)',
        'City'          => 'Varchar(255)',
        'Province'      => 'Varchar(255)',
        'PostalCode'    => 'Varchar(255)',
        'Email'         => 'Varchar(255)',
        'Phone'         => 'Varchar(255)',
        'Fax'           => 'Varchar(255)',
        'YouTubeURL'    => 'Varchar(255)',
        // Contact banner (App\Includes\BookingBanner): every sentence is the firm's own copy.
        'BookingBannerTitle'      => 'Varchar(255)',
        'BookingBannerBody'       => 'Text',
        'BookingBannerButtonText' => 'Varchar(100)',
        'BookingBannerDisclaimer' => 'Text',
    ];

    private static $has_one = [
        'ShareImage' => Image::class,
    ];

    private static $owns = [
        'ShareImage',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.ContactInfo', [
            TextField::create('StreetAddress', 'Street Address'),
            FieldGroup::create('City / Province / Postal Code',
                TextField::create('City', 'City'),
                TextField::create('Province', 'Province'),
                TextField::create('PostalCode', 'Postal Code')
            ),
            TextField::create('Email', 'Email'),
            TextField::create('Phone', 'Phone'),
            TextField::create('Fax', 'Fax'),
        ]);

        $fields->addFieldsToTab('Root.SEO', [
            UploadField::create('ShareImage', 'Default social sharing image')
                ->setFolderName('SEO')
                ->setAllowedMaxFileNumber(1)
                ->setDescription('Shown when a page is shared on social media and has no hero image of its own. Recommended 1200 x 630 px.'),
            TextField::create('YouTubeURL', 'YouTube channel URL')
                ->setDescription('Full URL. Shown in the footer and in the site schema alongside the social links above.'),
        ]);

        $fields->addFieldsToTab('Root.ContactBanner', [
            TextField::create('BookingBannerTitle', 'Heading')
                ->setDescription('The contact banner sits above the footer on most pages. Its phone, email and address come from Contact Info. An empty field hides its line.'),
            TextareaField::create('BookingBannerBody', 'Text under the heading')->setRows(2),
            TextField::create('BookingBannerButtonText', 'Button label')
                ->setDescription('The button links to the page whose URL is /contact/ and shows only when that page exists.'),
            TextareaField::create('BookingBannerDisclaimer', 'Disclaimer')->setRows(3)
                ->setDescription('Shown in small print under the button. Use the firm\'s approved wording exactly.'),
        ]);
    }
}
