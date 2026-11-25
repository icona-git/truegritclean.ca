<?php

namespace App\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\Validation\Validator;
use UndefinedOffset\NoCaptcha\Forms\NocaptchaField;

/**
 * Enforces the userforms spam-protection field, which is otherwise never validated.
 *
 * Form::validate() iterates Form::Fields() and calls validate() on each entry, but that
 * yields TOP-LEVEL fields only. userforms wraps every field in a UserFormsStepField (a
 * CompositeField), and CompositeField does not override FormField::validate(), which does
 * not recurse into children. The captcha therefore sits one level below anything that
 * validates, so NocaptchaField::validate() is never called: the widget renders, nothing
 * checks the answer, and any POST carrying a valid CSRF token is accepted.
 *
 * This validator walks the FLATTENED field list and validates the spam-protection field.
 *
 * CRITICAL -- the reCAPTCHA token (g-recaptcha-response) is SINGLE-USE. If it is sent to
 * Google's siteverify more than once in a request, the second call returns
 * error-codes:["timeout-or-duplicate"] and the submission is falsely rejected even for a
 * human who answered correctly. Every spam field on the form reads the same POST var, so we
 * must verify EXACTLY ONCE: prefer a verdict already computed elsewhere in this request,
 * and otherwise call Google against a single field. (Observed on an ICONA client's staging
 * site, 2026-09-10: fresh tokens rejected as timeout-or-duplicate, no submissions stored --
 * a double verification.)
 *
 * FAIL-CLOSED: with a wrong or mismatched key pair every submission is rejected, so the keys
 * in .env must be a matched pair from ONE reCAPTCHA registration whose domain list includes
 * the site's apex, www and staging hosts. Prove "solved passes, unsolved blocked" on staging
 * before production. The keys reach NocaptchaField through Injector properties in
 * app/_config/mysite.yml; plain config does not expand `ENV` backticks.
 *
 * The fail-open was found and fixed on framework 6.2.3 + userforms 7.1.1 (fleet audit,
 * site-register FLEET-AUDIT_userforms-captcha_2026-09-10.md). Checked on the starter's lock
 * (2026-09-22: framework 6.2.7, userforms 7.1.3, spamprotection 5.0.1, nocaptcha 3.1.0):
 * posts with no token or an empty token are refused and nothing is stored; siteverify is
 * called at most once per request (this validator logs "reused-existing-verdict" when the
 * field was already checked). A control run with this validator switched off was ALSO
 * refused on that lock: CompositeField declares CompositeFieldValidator in its
 * $field_validators, getValueForValidation() returns its children, and
 * FormField::validate() on the userforms step runs it, so NocaptchaField::validate() is
 * reached through the top-level field loop in Form::validate().
 * Keep the validator anyway. It makes enforcement independent of which upstream versions a
 * site runs, and its log line is the first thing to read when a form misbehaves.
 */
class SpamProtectionFieldValidator extends Validator
{
    public function php($data)
    {
        if (!isset($this->form)) {
            return true;
        }

        // Collect every spam-protection field on the flattened form.
        $spamFields = [];
        foreach ($this->form->Fields()->getDataFields() as $field) {
            if ($this->isSpamProtectionField($field)) {
                $spamFields[] = $field;
            }
        }
        if (empty($spamFields)) {
            return true;
        }

        // Prefer a verdict already computed this request; only call Google if none exists.
        $names = [];
        $preVerified = null;
        foreach ($spamFields as $f) {
            $names[] = $f->getName();
            if ($preVerified === null && method_exists($f, 'getVerifyResponse')) {
                $vr = $f->getVerifyResponse();
                if (is_array($vr)) {
                    $preVerified = $vr;
                }
            }
        }

        if (is_array($preVerified)) {
            $ok = !empty($preVerified['success']);
            $mechanism = 'reused-existing-verdict';
            $response = $preVerified;
        } else {
            // Verify ONE field only. All spam fields share the same single-use token.
            $result = $spamFields[0]->validate();
            $ok = $result->isValid();
            $mechanism = 'verified-once';
            $response = method_exists($spamFields[0], 'getVerifyResponse')
                ? $spamFields[0]->getVerifyResponse()
                : null;
        }

        // Diagnostic: field count is the tell for a double-verify inside this loop; mechanism
        // says whether something verified before us. Never logs the token or the secret.
        // token_len: 0 means the browser posted the form before the reCAPTCHA script filled the
        // hidden field (a client-side failure); a long token Google still rejects means the
        // site/secret pair is wrong at Google's end. Length only -- never the token.
        $token = Controller::curr()?->getRequest()?->requestVar('g-recaptcha-response');
        error_log(sprintf(
            'spam-protection: captcha check fields=%d names=%s mechanism=%s ok=%s token_len=%d google=%s',
            count($spamFields),
            implode(',', $names),
            $mechanism,
            $ok ? 'true' : 'false',
            strlen((string) $token),
            is_array($response)
                ? json_encode([
                    'success' => $response['success'] ?? null,
                    'score' => $response['score'] ?? null,
                    'error-codes' => $response['error-codes'] ?? null,
                ])
                : 'none'
        ));

        if ($ok) {
            return true;
        }

        $this->validationError(
            $spamFields[0]->getName(),
            _t(
                'UndefinedOffset\\NoCaptcha\\Forms\\NocaptchaField.EMPTY',
                'Please answer the captcha to prove you are not a robot'
            ),
            ValidationResult::TYPE_ERROR
        );
        return false;
    }

    /**
     * Scoped deliberately narrowly. Widening this to "every nested field" would change
     * validation behaviour for every other field type on every form on the site.
     */
    protected function isSpamProtectionField(FormField $field): bool
    {
        return $field instanceof NocaptchaField;
    }
}
