<?php

namespace App\Extensions;

use App\Forms\SpamProtectionFieldValidator;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\Validation\CompositeValidator;

/**
 * Adds SpamProtectionFieldValidator to every userforms form.
 *
 * UserForm::__construct() calls setValidator($this->getRequiredFields()) and then fires
 * updateForm(), so this hook runs after the module's own validator is in place. The
 * existing validator is preserved: it is wrapped in a CompositeValidator rather than
 * replaced, so required-field and conditional-display validation are untouched.
 *
 * See SpamProtectionFieldValidator for why this is necessary.
 */
class UserForm_Extension extends Extension
{
    public function updateForm()
    {
        $form = $this->owner;
        $existing = $form->getValidator();

        if ($existing instanceof CompositeValidator) {
            $composite = $existing;
        } else {
            $composite = CompositeValidator::create();

            if ($existing) {
                $composite->addValidator($existing);
            }
        }

        // updateForm() can fire more than once on a rebuilt form; do not stack duplicates.
        foreach ($composite->getValidators() as $validator) {
            if ($validator instanceof SpamProtectionFieldValidator) {
                return;
            }
        }

        $composite->addValidator(SpamProtectionFieldValidator::create());
        $form->setValidator($composite);
    }
}
