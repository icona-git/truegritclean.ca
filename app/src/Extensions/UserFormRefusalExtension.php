<?php

namespace App\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\Form;
use UndefinedOffset\NoCaptcha\Forms\NocaptchaField;

/**
 * What a visitor sees when a userforms form refuses a submission: the form's own page, what they typed,
 * and the spam check's message at the top of the form.
 *
 * Without it, the framework (FormRequestHandler::getValidationErrorResponse()) redirects to the Referer
 * header, and a browser that sends none (a privacy setting or extension, a no-referrer policy) is sent to
 * the HOME page: no form, no message, and the typed text parked in the session until the visitor happens
 * to open the form's page again. Here the Referer is still used when it is a page of this site, and the
 * form's own page is the fallback, so the visitor always comes back to the form.
 *
 * The spam check's message belongs to a field nobody sees (reCAPTCHA v3 is invisible, and the theme hides
 * its label), so it moves to the top of the form, where UserForm.ss prints form-wide messages and where
 * the redirect's #anchor lands. Every other field keeps its own message. The wording is in app/lang, under
 * nocaptcha's own keys.
 *
 * This decides where a refused post goes and where that one message shows, nothing more. Whether a post
 * is refused is decided by userforms and by SpamProtectionFieldValidator (added by UserForm_Extension).
 */
class UserFormRefusalExtension extends Extension
{
    public function updateForm(): void
    {
        $form = $this->getOwner();
        $form->setValidationResponseCallback(
            fn (ValidationResult $result) => $this->refusalResponse($form, $result)
        );
    }

    /**
     * The framework's own response for a refused post that is not AJAX (keep the result and the data in the
     * session, redirect to the form), with the spam message moved and a fallback when there is no Referer.
     * Returning null leaves AJAX requests to the framework's JSON response.
     */
    private function refusalResponse(Form $form, ValidationResult $result): ?HTTPResponse
    {
        $controller = $form->getController();
        $request = $controller?->getRequest();
        if (!$controller || !$request || $request->isAjax()) {
            return null;
        }

        $form->setSessionValidationResult($this->withSpamMessageOnForm($form, $result));
        $form->setSessionData($form->getData());

        // join_links() keeps only the last fragment, so a link that already ends in an #anchor (a form in an
        // elemental block) still gets exactly one: the form's.
        $page = $controller->getReturnReferer() ?: $controller->Link();
        return $controller->redirect(Controller::join_links($page, '#' . $form->FormName()));
    }

    /**
     * The same messages, except that any message on a spam-protection field becomes one form-wide message.
     * nocaptcha and the starter's validator can both report the refusal; the shared code keeps one copy.
     */
    private function withSpamMessageOnForm(Form $form, ValidationResult $result): ValidationResult
    {
        $spamFields = [];
        foreach ($form->Fields()->getDataFields() as $field) {
            if ($field instanceof NocaptchaField) {
                $spamFields[$field->getName()] = true;
            }
        }
        $messages = $result->getMessages();
        $hasSpamMessage = false;
        foreach ($messages as $message) {
            if (isset($spamFields[$message['fieldName'] ?? ''])) {
                $hasSpamMessage = true;
                break;
            }
        }
        if (!$hasSpamMessage) {
            return $result;
        }

        $moved = ValidationResult::create();
        foreach ($messages as $code => $message) {
            $fieldName = $message['fieldName'] ?? '';
            $type = $message['messageType'] ?? ValidationResult::TYPE_ERROR;
            $cast = $message['messageCast'] ?? ValidationResult::CAST_TEXT;
            if (isset($spamFields[$fieldName])) {
                $moved->addError($message['message'], $type, 'spam-protection', $cast);
            } else {
                $moved->addFieldError($fieldName, $message['message'], $type, is_string($code) ? $code : '', $cast);
            }
        }
        return $moved;
    }
}
