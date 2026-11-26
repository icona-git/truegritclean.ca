<?php

namespace App\Models;

use SilverStripe\Assets\File;
use SilverStripe\Control\Director;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Security;
use SilverStripe\UserForms\Model\Submission\SubmittedFormField;
use SilverStripe\Versioned\Versioned;

/**
 * One submission's photos from an App\Models\EditablePhotosField: the Submissions tab, the email and the CSV export
 * list each photo with a link. The photos are draft (protected) files; a link opens for a logged-in administrator.
 * Deleting the submission (or this row) deletes its photos, so "delete a quote request" removes everything in it.
 */
class SubmittedPhotosField extends SubmittedFormField
{
    private static $table_name = 'SubmittedPhotosField';

    private static $many_many = [
        'Photos' => File::class,
    ];

    /** File IDs saved for this submission before the row was written (EditablePhotosField::getValueFromData()). */
    private array $pendingPhotoIDs = [];

    public function setPendingPhotoIDs(array $ids): static
    {
        $this->pendingPhotoIDs = array_values(array_filter(array_map('intval', $ids)));
        return $this;
    }

    protected function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->pendingPhotoIDs) {
            $this->Photos()->addMany($this->pendingPhotoIDs);
            $this->pendingPhotoIDs = [];
        }
    }

    protected function onBeforeDelete()
    {
        parent::onBeforeDelete();
        $files = $this->photoFiles();
        // The join rows first, while the draft-only photos are still visible to the relation.
        Versioned::withVersionedMode(function () {
            Versioned::set_stage(Versioned::DRAFT);
            $this->Photos()->removeAll();
        });
        foreach ($files as $file) {
            // Archive removes the file from both stages; with keep_archived_assets off (the default) the stored file
            // is deleted too.
            $file->doArchive();
        }
    }

    /**
     * The photos as File records, read from the Draft stage where uploads live. Before the row is written (the email
     * is built a moment before that when submissions are not saved) they come from the pending IDs.
     */
    public function photoFiles(): ArrayList
    {
        return Versioned::withVersionedMode(function () {
            // Inside the Draft stage: on the front end (Live) the relation would find none of the draft-only photos.
            Versioned::set_stage(Versioned::DRAFT);
            // In upload order (the join rows' order), not by file name.
            $ids = $this->isInDB()
                ? $this->Photos()->orderBy('"SubmittedPhotosField_Photos"."ID"')->column('ID')
                : $this->pendingPhotoIDs;
            $files = ArrayList::create();
            foreach ($ids as $id) {
                $file = File::get()->byID((int) $id);
                if ($file && $file->exists()) {
                    $files->push($file);
                }
            }
            return $files;
        });
    }

    /** One line per photo: its name as a link. In an email to a visitor's recipient, a note that a login is needed. */
    public function getFormattedValue(): ?DBField
    {
        $files = $this->photoFiles();
        if (!$files->count()) {
            return DBField::create_field('HTMLText', htmlspecialchars((string) $this->Value, ENT_QUOTES));
        }
        $lines = [];
        foreach ($files as $file) {
            $url = $file->getURL(false);
            $lines[] = $url
                ? sprintf('<a href="%s" target="_blank">%s</a>', htmlspecialchars(Director::absoluteURL($url), ENT_QUOTES), htmlspecialchars($file->Name, ENT_QUOTES))
                : htmlspecialchars($file->Name, ENT_QUOTES);
        }
        $html = implode('<br>', $lines);
        if (!Security::getCurrentUser()) {
            $html .= '<br><em>' . htmlspecialchars(_t(
                'SilverStripe\\UserForms\\Model\\Submission\\SubmittedFileField.YOUMUSTBELOGGEDIN',
                'You must be logged in to view this file'
            ), ENT_QUOTES) . '</em>';
        }
        /** @var DBHTMLText $field */
        $field = DBField::create_field('HTMLText', $html);
        return $field;
    }

    /** The CSV export: one link per photo, one per line. */
    public function getExportValue()
    {
        $links = [];
        foreach ($this->photoFiles() as $file) {
            $url = $file->getURL(false);
            if ($url) {
                $links[] = Director::absoluteURL($url);
            }
        }
        return implode("\n", $links);
    }
}
