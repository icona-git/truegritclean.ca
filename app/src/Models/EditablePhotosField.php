<?php

namespace App\Models;

use App\Forms\EmailPhotos;
use App\Forms\PhotosUploadField;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\UserForms\Model\EditableFormField;

/**
 * A userforms field type that takes several photos at once (Form Fields > "Photo Upload Field (several photos)").
 * userforms' own File Upload Field takes one file; the quote form asks for up to 10 site photos (BUILD-HANDOFF.md
 * Phase 5, approach approved by Dave on 2026-11-26). Built in the site's code, no extra package.
 *
 *  - Front end: App\Forms\PhotosUploadField, which checks every photo before the submission is accepted.
 *  - Delivery: the photos are never stored on the server (Dave, 2026-11-27). After userforms accepts the submission,
 *    App\Forms\EmailPhotos shrinks each one to a JPEG in memory, and App\Extensions\QuotePhotosEmailExtension attaches
 *    them to each recipient's email. Nothing is written to Files or to the database; PHP deletes the uploads when the
 *    request ends. If the email cannot be sent, App\Pages\ContactPageController tells the visitor instead of showing the
 *    thank-you.
 *  - The submission row is an App\Models\SubmittedPhotosField that carries the attachments for this request only; its
 *    value (the email text, and the Submissions tab if saving is ever turned back on) lists what was attached.
 *
 * userforms' process() only knows its own file field (it reads $_FILES[<Name>] as one file and stores it in Files), so
 * this field does not extend EditableFileField. It prepares the photos in getValueFromData(), which process() calls once
 * per submission, and hands them to the submission row created a moment earlier by getSubmittedFormField().
 */
class EditablePhotosField extends EditableFormField
{
    private static $singular_name = 'Photo Upload Field (several photos)';

    private static $plural_name = 'Photo Upload Fields (several photos)';

    private static $table_name = 'EditablePhotosField';

    private static $db = [
        'MaxFiles' => 'Int',
        'MaxFileSizeMB' => 'Int',
    ];

    private static $defaults = [
        'MaxFiles' => 10,
        'MaxFileSizeMB' => 10,
    ];

    /**
     * What the field accepts. Not HEIC: the image libraries cannot read it, and an iPhone's Safari converts its photos
     * to JPEG when the page does not accept HEIC.
     */
    private static array $allowed_extensions = ['jpg', 'jpeg', 'png'];

    /** PHP refuses more files than max_file_uploads in one request; userforms forms carry no other files. */
    private const MAX_FILES_LIMIT = 20;

    /** The submission row of the request being processed (see the class comment). */
    private ?SubmittedPhotosField $submittedField = null;

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName(['Default', 'Placeholder']);
            $fields->addFieldsToTab('Root.Main', [
                NumericField::create('MaxFiles', 'Most photos per submission')
                    ->setDescription('Up to ' . min(self::MAX_FILES_LIMIT, $this->phpMaxFiles()) . '.'),
                NumericField::create('MaxFileSizeMB', 'Largest photo, in MB')
                    ->setDescription('The server allows up to ' . $this->phpMaxFileSizeMB() . ' MB per file and '
                        . round(PhotosUploadField::iniBytes('post_max_size') / 1024 / 1024) . ' MB per submission.'),
                LiteralField::create('PhotosEmailNote', '<p class="form__field-description">'
                    . 'JPG or PNG photos. They are not stored on this website: each one is resized and attached to the '
                    . 'notification email, then discarded. If the photos together are too large for one email, the '
                    . 'email lists the ones it could not carry.</p>'),
            ]);
        });
        return parent::getCMSFields();
    }

    public function validate(): ValidationResult
    {
        $result = parent::validate();
        $maxFiles = min(self::MAX_FILES_LIMIT, $this->phpMaxFiles());
        if ($this->MaxFiles < 1 || $this->MaxFiles > $maxFiles) {
            $result->addError("Most photos per submission must be between 1 and {$maxFiles}.");
        }
        if ($this->MaxFileSizeMB < 1 || $this->MaxFileSizeMB > $this->phpMaxFileSizeMB()) {
            $result->addError("Largest photo must be between 1 and {$this->phpMaxFileSizeMB()} MB (the server's limit).");
        }
        return $result;
    }

    public function getIcon()
    {
        // userforms looks for images/<shortclass>.png in its own module; borrow its file field's icon.
        $resource = ModuleLoader::getModule('silverstripe/userforms')?->getResource('images/editablefilefield.png');
        return $resource && $resource->exists() ? $resource->getURL() : '';
    }

    public function getFormField()
    {
        $field = PhotosUploadField::create($this->Name, $this->Title ?: false)
            ->setMaxFiles(max(1, (int) $this->MaxFiles))
            ->setMaxFileSize(max(1, (int) $this->MaxFileSizeMB) * 1024 * 1024)
            ->setAllowedExtensions(static::config()->get('allowed_extensions'));
        $this->doUpdateFormField($field);
        return $field;
    }

    public function getSubmittedFormField()
    {
        $this->submittedField = SubmittedPhotosField::create();
        return $this->submittedField;
    }

    /**
     * Prepares the photos of the submission being processed as email attachments and returns the text the email shows
     * for this field: what is attached, and any photo that is not, with the reason. Called by userforms' process()
     * after the form passed validation.
     */
    public function getValueFromData($data)
    {
        $files = $this->getFormField()->uploadedFiles();
        if (!$files) {
            return '';
        }
        $built = EmailPhotos::singleton()->build($files);
        $this->submittedField?->setAttachments($built['attachments']);

        $lines = [];
        if ($built['attachments']) {
            $count = count($built['attachments']);
            $lines[] = _t(__CLASS__ . '.ATTACHED', 'One photo attached to this email:|{count} photos attached to this email:', ['count' => $count]);
            foreach ($built['attachments'] as $attachment) {
                $lines[] = $attachment['name'];
            }
        }
        if ($built['skipped']) {
            $lines[] = _t(__CLASS__ . '.NOT_ATTACHED', 'Not attached:');
            foreach ($built['skipped'] as $skip) {
                $lines[] = _t(__CLASS__ . '.' . $skip['reason'], '{name}: not attached.', ['name' => $skip['name']]);
            }
        }
        return implode("\n", $lines);
    }

    private function phpMaxFiles(): int
    {
        return (int) ini_get('max_file_uploads') ?: self::MAX_FILES_LIMIT;
    }

    private function phpMaxFileSizeMB(): int
    {
        $limits = array_filter([PhotosUploadField::iniBytes('upload_max_filesize'), PhotosUploadField::iniBytes('post_max_size')]);
        return $limits ? (int) floor(min($limits) / 1024 / 1024) : 100;
    }
}
