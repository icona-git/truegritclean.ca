<?php

namespace App\Models;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\UserForms\Model\Submission\SubmittedFormField;

/**
 * One submission's photos from an App\Models\EditablePhotosField. It carries the resized photos for the request that
 * sends the email (App\Extensions\QuotePhotosEmailExtension attaches them) and nothing else: the photos are never
 * written anywhere. Its value is the text the email shows for the field, one line each.
 *
 * The class (and its table name) stays so that rows written before 2026-11-27, when photos were stored as files, still
 * load. Those rows' photo links are gone with the files.
 */
class SubmittedPhotosField extends SubmittedFormField
{
    private static $table_name = 'SubmittedPhotosField';

    /** @var list<array{name: string, data: string}> resized photos for this request's emails, in upload order */
    private array $attachments = [];

    public function setAttachments(array $attachments): static
    {
        $this->attachments = array_values($attachments);
        return $this;
    }

    /** @return list<array{name: string, data: string}> */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /** The value's lines, kept as lines in the HTML email (userforms' default shows them run together). */
    public function getFormattedValue(): ?DBField
    {
        return DBField::create_field('HTMLText', str_replace("\n", '<br>', htmlspecialchars((string) $this->Value, ENT_QUOTES)));
    }
}
