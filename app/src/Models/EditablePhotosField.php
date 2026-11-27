<?php

namespace App\Models;

use App\Forms\EmailPhotos;
use App\Forms\PhotosUploadField;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DB;
use SilverStripe\UserForms\Model\EditableFormField;

/**
 * A userforms field type that takes several photos at once (Form Fields > "Photo Upload Field (several photos)").
 * userforms' own File Upload Field takes one file; the quote form asks for up to 5 site photos (BUILD-HANDOFF.md
 * Phase 5, approach approved by Dave on 2026-11-26; 5 rather than 10 since the upload audit of 2026-11-27, with more
 * photos following by email). Built in the site's code, no extra package.
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
        'MaxFiles' => 5,
        'MaxFileSizeMB' => 10,
    ];

    /**
     * What the field accepts. Not HEIC: the image libraries cannot read it, and an iPhone's Safari converts its photos
     * to JPEG when the page does not accept HEIC.
     */
    private static array $allowed_extensions = ['jpg', 'jpeg', 'png'];

    /**
     * The most photos one submission may carry, whatever the CMS field says (Dave, 2026-11-27). Every photo is opened
     * and resized on the server, so the count is what one request can cost; a visitor with more sends them by email
     * once the firm replies. A row saved with a higher number is lowered by requireDefaultRecords() at db:build.
     */
    public const MAX_FILES_LIMIT = 5;

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

    /**
     * Lowers MaxFiles on rows saved before MAX_FILES_LIMIT was 5 (staging's field said 10), in draft and live. Runs at
     * db:build, so a deploy needs no CMS step. It only ever lowers a number that validate() would now refuse.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $table = static::config()->get('table_name');
        foreach ([$table, $table . '_Live'] as $name) {
            if (!DB::get_schema()->hasTable($name)) {
                continue;
            }
            DB::prepared_query(
                sprintf('UPDATE "%s" SET "MaxFiles" = ? WHERE "MaxFiles" > ?', $name),
                [self::MAX_FILES_LIMIT, self::MAX_FILES_LIMIT]
            );
            if (DB::affected_rows() > 0) {
                DB::alteration_message("$name: MaxFiles lowered to " . self::MAX_FILES_LIMIT, 'changed');
            }
        }
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
            ->setMaxFiles(min(self::MAX_FILES_LIMIT, max(1, (int) $this->MaxFiles)))
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
