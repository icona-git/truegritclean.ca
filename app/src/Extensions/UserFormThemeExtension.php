<?php

namespace App\Extensions;

use App\Forms\PhotosUploadField;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\UserForms\Form\UserForm;
use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\UserForms\Model\EditableFormField\EditableEmailField;
use SilverStripe\UserForms\Model\EditableFormField\EditableTextField;

/**
 * The parts of the Figma quote form (design/figma-export/quote.html) that a template cannot set: input types and
 * the submit button. Field markup, sections and pill radios are theme templates
 * (themes/app-theme/templates/SilverStripe/UserForms); app/_config/userforms.yml registers this class.
 *
 * On EditableFormField (afterUpdateFormField, after userforms has built each field):
 *   - a text field whose Autocomplete is "tel" becomes type="tel", so a phone opens its number pad;
 *   - email fields get autocomplete="email" (userforms offers no Autocomplete setting on them);
 *   - number fields become type="number" with min="0" (square footage and unit counts are never negative).
 *     userforms renders them as plain text boxes otherwise, and cannot store a minimum of 0.
 *
 * On UserForm (updateForm): the submit control is a <button> with the Figma classes, not an <input>; and a form with
 * a photo field (App\Forms\PhotosUploadField) is sent as multipart/form-data, which Form sets by itself only for
 * subclasses of FileField.
 *
 * Nothing here decides what is accepted: required fields, the email check and the spam check stay with userforms,
 * SpamProtectionFieldValidator and the field classes, and the server checks every value again on submit.
 */
class UserFormThemeExtension extends Extension
{
    public function afterUpdateFormField(FormField $field): void
    {
        $owner = $this->getOwner();
        if (!$owner instanceof EditableFormField) {
            return;
        }

        if ($owner instanceof EditableTextField && (int) $owner->Rows <= 1 && $owner->Autocomplete === 'tel') {
            $field->setAttribute('type', 'tel');
        }

        if ($owner instanceof EditableEmailField) {
            $field->setAttribute('autocomplete', 'email');
        }

        if ($field instanceof NumericField) {
            $field->setHTML5(true);
            $field->setAttribute('inputmode', 'numeric');
            if (!$owner->MinValue) {
                $field->setAttribute('min', '0');
            }
        }
    }

    public function updateForm(): void
    {
        $form = $this->getOwner();
        if (!$form instanceof UserForm) {
            return;
        }

        foreach ($form->Fields()->dataFields() as $field) {
            if ($field instanceof PhotosUploadField) {
                $form->setEncType(Form::ENC_TYPE_MULTIPART);
                break;
            }
        }

        $submit = $form->Actions()->fieldByName('action_process');
        if ($submit instanceof FormAction) {
            $submit->setUseButtonTag(true);
            $submit->addExtraClass('button button--primary project-form__button');
        }
    }
}
