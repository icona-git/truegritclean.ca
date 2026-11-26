<?php

namespace App\Models;

use App\Forms\PhotosUploadField;
use Psr\Log\LoggerInterface;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Upload;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\UserForms\Control\UserDefinedFormAdmin;
use SilverStripe\UserForms\Control\UserDefinedFormController;
use SilverStripe\UserForms\Extension\UserFormFileExtension;
use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\Versioned\Versioned;

/**
 * A userforms field type that takes several photos at once (Form Fields > "Photo Upload Field (several photos)").
 * userforms' own File Upload Field takes one file; the quote form asks for up to 10 site photos (BUILD-HANDOFF.md
 * Phase 5, approach approved by Dave on 2026-11-26). Built in the site's code, no extra package.
 *
 *  - Front end: App\Forms\PhotosUploadField, which checks every photo before the submission is accepted.
 *  - Saving: after userforms accepts the submission, each photo is stored as a draft (protected) file in
 *    Files > Form-submissions/page-<form>/photos-field-<field>, a folder only administrators can open, like userforms'
 *    own uploads. Draft files are never public; a logged-in administrator opens them through File::canView().
 *  - The submission row is an App\Models\SubmittedPhotosField: the Submissions tab and the email list each photo with
 *    a link (the email says a login is needed). Photos are linked, never attached: ten phone photos would exceed mail
 *    size limits and look like spam. Deleting a submission deletes its photos.
 *
 * userforms' process() only knows its own file field (it reads $_FILES[<Name>] as one file), so this field does not
 * extend EditableFileField. It saves the photos in getValueFromData(), which process() calls once per submission,
 * and hands them to the submission row created a moment earlier by getSubmittedFormField().
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

    /** What the field accepts. HEIC is the iPhone's own format (app/_config/userforms.yml lets Files store it). */
    private static array $allowed_extensions = ['jpg', 'jpeg', 'png', 'heic'];

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
                LiteralField::create('PhotosFolderNote', '<p class="form__field-description">'
                    . 'Photos are saved in Files &gt; ' . htmlspecialchars($this->folderPath(), ENT_QUOTES)
                    . ', which only administrators can open. Deleting a submission deletes its photos.</p>'),
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
     * Saves the photos of the submission being processed and returns their names, one per line (the submission's text
     * value: CSV export and search). Called by userforms' process() after the form passed validation.
     */
    public function getValueFromData($data)
    {
        $field = $this->getFormField();
        $files = $field->uploadedFiles();
        if (!$files) {
            return '';
        }
        $folder = $this->photosFolder();
        $saved = [];
        $names = [];
        Versioned::withVersionedMode(function () use ($files, $field, $folder, &$saved, &$names) {
            // Draft, like userforms' own uploads (file_upload_stage): a draft file is protected, never public.
            Versioned::set_stage(UserDefinedFormController::config()->get('file_upload_stage') ?: Versioned::DRAFT);
            foreach ($files as $tmpFile) {
                $upload = Upload::create();
                $upload->setValidator($field->createValidator());
                try {
                    $upload->loadIntoFile($tmpFile, null, $folder->getFilename());
                } catch (ValidationException $e) {
                    // Checked already in PhotosUploadField::validate(); a failure here is the server, not the photo.
                    Injector::inst()->get(LoggerInterface::class)->error(
                        'Quote form photo not saved: ' . $tmpFile['name'] . ': ' . $e->getMessage()
                    );
                    continue;
                }
                /** @var File $file */
                $file = $upload->getFile();
                if (!$file) {
                    continue;
                }
                $file->ShowInSearch = 0;
                $file->UserFormUpload = UserFormFileExtension::USER_FORM_UPLOAD_TRUE;
                $file->write();
                if (class_exists(AssetAdmin::class)) {
                    AssetAdmin::singleton()->generateThumbnails($file);
                }
                $saved[] = $file->ID;
                $names[] = $file->Name;
            }
        });
        if ($this->submittedField) {
            $this->submittedField->setPendingPhotoIDs($saved);
        }
        if (count($names) < count($files)) {
            $names[] = _t(__CLASS__ . '.NOT_SAVED', '(Not saved: {count} of the photos. The server could not store them.)', ['count' => count($files) - count($names)]);
        }
        return implode("\n", $names);
    }

    /**
     * The protected folder for this field's photos, Form-submissions/page-<form>/photos-field-<field>, created on first
     * use. The path is fixed, so nothing is written to the field itself during a visitor's submission. userforms makes
     * Form-submissions administrators-only when it creates it; the subfolders inherit that.
     */
    public function photosFolder(): Folder
    {
        return UserDefinedFormAdmin::getFormSubmissionFolder($this->subfolder());
    }

    private function folderPath(): string
    {
        return UserDefinedFormAdmin::config()->get('form_submissions_folder') . '/' . $this->subfolder();
    }

    private function subfolder(): string
    {
        return sprintf('page-%d/photos-field-%d', $this->ParentID, $this->ID);
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
