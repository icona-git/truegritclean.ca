<?php
namespace App\Extensions;

use App\Models\Claims;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
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
        // Service-area business: where they work, shown in the footer instead of a street address.
        'ServiceArea'   => 'Text',
        // Footer fine print (App\Includes\Footer).
        'FooterExclusions' => 'Text',
        'PhotoCredits'     => 'Text',
        // CTA band (App\Includes\BookingBanner): every sentence is the client's own copy.
        'BookingBannerTitle'      => 'Varchar(255)',
        'BookingBannerBody'       => 'Text',
        'BookingBannerButtonText' => 'Varchar(100)',
        'BookingBannerPhoneText'  => 'Varchar(100)',
        // Not rendered by this site's CTA band (the design has no disclaimer); kept so the column stays.
        'BookingBannerDisclaimer' => 'Text',
        // Claims (App\Models\Claims::CLAIMS): default off; content that depends on one stays hidden until ticked.
        'ClaimInsured'         => 'Boolean',
        'ClaimWorkSafeBC'      => 'Boolean',
        'ClaimHEPA'            => 'Boolean',
        'ClaimSignedChecklist' => 'Boolean',
        'ClaimJobPhotos'       => 'Boolean',
        'ClaimMinimumPrice'    => 'Boolean',
    ];

    private static $has_one = [
        'ShareImage' => Image::class,
    ];

    private static $owns = [
        'ShareImage',
    ];

    /**
     * The phone number for a tel: link: digits only, with +1 in front of a 10-digit North American
     * number ("825-882-3881" gives "+18258823881"). Anything else is returned as digits, or empty.
     */
    public function PhoneLink(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->owner->Phone);
        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+' . $digits;
        }
        return $digits;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $tagline = $fields->dataFieldByName('Tagline');
        if ($tagline) {
            $tagline->setDescription('Shown in the footer under the logo.');
        }

        $fields->addFieldsToTab('Root.ContactInfo', [
            TextField::create('StreetAddress', 'Street Address')
                ->setDescription('Leave empty for a service-area business: an empty address stays off the site and out of the schema.'),
            FieldGroup::create('City / Province / Postal Code',
                TextField::create('City', 'City'),
                TextField::create('Province', 'Province'),
                TextField::create('PostalCode', 'Postal Code')
            ),
            TextField::create('Email', 'Email'),
            TextField::create('Phone', 'Phone')
                ->setDescription('As visitors should read it, e.g. 825-882-3881. Tap-to-call links add +1 themselves.'),
            TextareaField::create('ServiceArea', 'Service area')->setRows(2)
                ->setDescription('Shown in the footer\'s Contact column. A line break here is a line break on the site.'),
            TextField::create('Fax', 'Fax'),
        ]);

        $fields->addFieldsToTab('Root.Footer', [
            TextareaField::create('FooterExclusions', 'Exclusions line')->setRows(3)
                ->setDescription('Small print at the foot of every page: the work the company does not take on.'),
            TextareaField::create('PhotoCredits', 'Photo credits')->setRows(3)
                ->setDescription('Small print under the exclusions line. Keep it in step with the stock photos actually used.'),
        ]);

        $fields->addFieldsToTab('Root.SEO', [
            UploadField::create('ShareImage', 'Default social sharing image')
                ->setFolderName('SEO')
                ->setAllowedMaxFileNumber(1)
                ->setDescription('Shown when a page is shared on social media and has no hero image of its own. Recommended 1200 x 630 px.'),
            TextField::create('YouTubeURL', 'YouTube channel URL')
                ->setDescription('Full URL. Shown in the site schema alongside the social links above.'),
        ]);

        $claimFields = [
            LiteralField::create('ClaimsIntro', '<p class="message notice">Tick a box only once the business has confirmed it is true. '
                . 'While a box is unticked, everything that depends on it stays off the site: trust badges, checklist and FAQ items '
                . 'that tick it under "Show only when confirmed", and any sentence wrapped in [claim key=...]...[/claim].</p>'),
        ];
        foreach (Claims::CLAIMS as $key => [$field, $label]) {
            $claimFields[] = CheckboxField::create($field, $label)
                ->setDescription(sprintf('Key for items and sentences: %s', $key));
        }
        $fields->addFieldsToTab('Root.Claims', $claimFields);

        $fields->addFieldsToTab('Root.ContactBanner', [
            TextField::create('BookingBannerTitle', 'Heading')
                ->setDescription('The yellow band above the footer. An empty heading hides the whole band.'),
            TextareaField::create('BookingBannerBody', 'Text under the heading')->setRows(2)
                ->setDescription('Home page only. Other pages show the heading and button.'),
            TextField::create('BookingBannerButtonText', 'Button label')
                ->setDescription('The button links to the quote page (the site\'s contact form page) and shows only when that page exists.'),
            TextField::create('BookingBannerPhoneText', 'Text before the phone number')
                ->setDescription('Home page only, under the button, e.g. "or call or text". The phone number from Contact Info follows it.'),
        ]);
    }
}
