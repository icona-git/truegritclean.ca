<?php

namespace App\Extensions;

use App\Models\SubmittedPhotosField;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Extension;
use SilverStripe\UserForms\Model\Recipient\EmailRecipient;

/**
 * Attaches the quote form's photos to each recipient's email (userforms' updateEmail hook, which fires once per
 * recipient just before the email is sent). The photos come from the submission's App\Models\SubmittedPhotosField,
 * already resized in memory by App\Forms\EmailPhotos; nothing is read from or written to Files.
 *
 * userforms attaches files itself only for its own File Upload Field, and only File records that were saved (it skips
 * any with ID 0), so this field cannot use that path.
 *
 * Like userforms' own attachments, none are added for a recipient set to "Hide form data from email".
 * Registered on UserDefinedFormController in app/_config/userforms.yml.
 */
class QuotePhotosEmailExtension extends Extension
{
    public function updateEmail(Email $email, EmailRecipient $recipient, array $emailData): void
    {
        if ((bool) $recipient->HideFormData) {
            return;
        }
        foreach ($emailData['Fields'] ?? [] as $field) {
            if (!$field instanceof SubmittedPhotosField) {
                continue;
            }
            foreach ($field->getAttachments() as $attachment) {
                $email->addAttachmentFromData($attachment['data'], $attachment['name'], 'image/jpeg');
            }
        }
    }
}
