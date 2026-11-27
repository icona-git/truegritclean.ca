<?php

namespace App\Forms;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FormField;

/**
 * The front-end side of the quote form's photo field (App\Models\EditablePhotosField): one file input that takes
 * several photos at once, in the Figma drop zone (themes/app-theme/templates/App/Forms/PhotosUploadField*.ss).
 *
 * userforms' own file field takes one file. The input here is named "<Name>[]" with `multiple`, so PHP files every
 * chosen photo under $_FILES[<Name>]; validate() checks each one before userforms accepts the submission, and
 * EditablePhotosField prepares them for the email afterwards. Nothing is stored, here or anywhere.
 *
 * Checks, all server-side (the browser checks the same limits first, js/src/photos.js, but nothing relies on it):
 * at most MaxFiles photos; each one a real upload, complete, not empty, within MaxFileSize, with an allowed
 * extension, and with content that matches its extension (MimeUploadValidator, the site's Upload_Validator).
 */
class PhotosUploadField extends FormField
{
    protected int $maxFiles = 5;

    /** Bytes. */
    protected int $maxFileSize = 10 * 1024 * 1024;

    /** @var string[] lower case, no dot */
    protected array $allowedExtensions = ['jpg', 'jpeg', 'png'];

    /**
     * The same types as content types, added to the input's accept list. An iPhone's Safari reads them and converts
     * its HEIC photos to JPEG as it uploads them.
     */
    private const ACCEPT_TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];

    public function setMaxFiles(int $max): static
    {
        $this->maxFiles = max(1, $max);
        return $this;
    }

    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }

    public function setMaxFileSize(int $bytes): static
    {
        $this->maxFileSize = max(1, $bytes);
        return $this;
    }

    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    public function setAllowedExtensions(array $extensions): static
    {
        $this->allowedExtensions = array_values(array_map('strtolower', $extensions));
        return $this;
    }

    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    public function Type()
    {
        return 'file photos';
    }

    public function getAttributes()
    {
        $accept = [];
        foreach ($this->allowedExtensions as $ext) {
            $accept[] = '.' . $ext;
        }
        foreach ($this->allowedExtensions as $ext) {
            $accept[] = self::ACCEPT_TYPES[$ext] ?? null;
        }
        $accept = array_values(array_unique(array_filter($accept)));
        $attributes = array_merge(parent::getAttributes(), [
            'type' => 'file',
            'name' => $this->getName() . '[]',
            'value' => null,
            'multiple' => true,
            'accept' => implode(',', $accept),
            // Read by js/src/photos.js, which shows the chosen photos and refuses too many or too large ones before
            // the form is sent (a post over PHP's post_max_size would arrive empty).
            'data-max-files' => $this->maxFiles,
            'data-max-size' => $this->maxFileSize,
            'data-max-total' => $this->maxTotalBytes(),
            'data-msg-count' => $this->tooManyMessage(),
            'data-msg-size' => _t(__CLASS__ . '.TOO_LARGE_JS', '{name} is larger than {size}.', ['size' => File::format_size($this->maxFileSize)]),
            'data-msg-type' => _t(__CLASS__ . '.WRONG_TYPE_JS', '{name} is not a JPG or PNG photo.'),
            'data-msg-total' => _t(__CLASS__ . '.TOO_MUCH', 'These photos add up to more than {size}. Choose fewer or smaller photos.', ['size' => File::format_size($this->maxTotalBytes())]),
            'data-msg-selected-one' => _t(__CLASS__ . '.SELECTED_ONE', '1 photo selected.'),
            'data-msg-selected' => _t(__CLASS__ . '.SELECTED', '{count} photos selected.'),
        ]);
        return $attributes;
    }

    /**
     * Shown by the browser (js/src/photos.js) and by validate() when more than MaxFiles photos are chosen. The limit is
     * small on purpose (App\Models\EditablePhotosField::MAX_FILES_LIMIT); the rest can follow by email.
     */
    public function tooManyMessage(): string
    {
        return _t(
            __CLASS__ . '.TOO_MANY',
            'Choose up to {max} photos. You can send more by email once we reply.',
            ['max' => $this->maxFiles]
        );
    }

    /** Largest request PHP accepts, less 1 MB for the form's other fields. */
    public function maxTotalBytes(): int
    {
        $post = self::iniBytes('post_max_size');
        return $post > 0 ? max(1, $post - 1024 * 1024) : $this->maxFiles * $this->maxFileSize;
    }

    /**
     * The photos posted under this field's name, one array per file in PHP's single-file shape (name, type, tmp_name,
     * error, size). Empty slots (no file chosen) are left out.
     *
     * @return array<int, array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    public function uploadedFiles(): array
    {
        return self::filesFrom($_FILES[$this->getName()] ?? null);
    }

    public static function filesFrom(mixed $raw): array
    {
        if (!is_array($raw) || !isset($raw['name'])) {
            return [];
        }
        $files = [];
        $names = (array) $raw['name'];
        foreach (array_keys($names) as $key) {
            $file = [
                'name' => (string) ($names[$key] ?? ''),
                'type' => (string) (((array) ($raw['type'] ?? []))[$key] ?? ''),
                'tmp_name' => (string) (((array) ($raw['tmp_name'] ?? []))[$key] ?? ''),
                'error' => (int) (((array) ($raw['error'] ?? []))[$key] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) (((array) ($raw['size'] ?? []))[$key] ?? 0),
            ];
            if ($file['error'] === UPLOAD_ERR_NO_FILE || ($file['name'] === '' && $file['tmp_name'] === '')) {
                continue;
            }
            $files[] = $file;
        }
        return $files;
    }

    /** A fresh validator with this field's rules: the site's Upload_Validator, i.e. MimeUploadValidator. */
    public function createValidator(): Upload_Validator
    {
        /** @var Upload_Validator $validator */
        $validator = Injector::inst()->create(Upload_Validator::class);
        $validator->setAllowedExtensions($this->allowedExtensions);
        $validator->setAllowedMaxFileSize($this->maxFileSize);
        return $validator;
    }

    public function validate(): ValidationResult
    {
        $this->beforeExtending('updateValidate', function (ValidationResult $result) {
            $files = $this->uploadedFiles();
            $name = $this->getName();

            if (!$files) {
                if ($this->Required()) {
                    $result->addFieldError($name, _t(__CLASS__ . '.REQUIRED', 'Add at least one photo.'));
                }
                return;
            }
            if (count($files) > $this->maxFiles) {
                $result->addFieldError($name, $this->tooManyMessage());
                return;
            }
            // One message for the field: SilverStripe shows a field's last message only, so every refused photo is
            // named in the same sentence list.
            $errors = [];
            foreach ($files as $file) {
                $label = $file['name'] !== '' ? $file['name'] : _t(__CLASS__ . '.A_PHOTO', 'A photo');
                if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    $errors[] = _t(__CLASS__ . '.TOO_LARGE', '{name} is larger than {size}.', [
                        'name' => $label,
                        'size' => File::format_size($this->maxFileSize),
                    ]);
                    continue;
                }
                $validator = $this->createValidator();
                $validator->setTmpFile($file);
                if (!$validator->validate()) {
                    foreach ($validator->getErrors() as $error) {
                        $errors[] = $label . ': ' . rtrim($error, '.') . '.';
                    }
                }
            }
            if ($errors) {
                $errors[] = _t(__CLASS__ . '.CHOOSE_AGAIN', 'Please choose your photos again.');
                $result->addFieldError($name, implode(' ', $errors));
            }
        });
        return parent::validate();
    }

    /** php.ini sizes such as "100M" in bytes; 0 when unset or unlimited. */
    public static function iniBytes(string $key): int
    {
        $value = trim((string) ini_get($key));
        if ($value === '' || $value === '-1' || $value === '0') {
            return 0;
        }
        $number = (int) $value;
        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
