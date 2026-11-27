<?php

namespace App\Pages;

use App\Control\RecordingMailer;
use App\Forms\PhotosUploadField;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\Form;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\UserForms\Control\UserDefinedFormController;
use Symfony\Component\Mailer\MailerInterface;

/**
 * The quote form's submit step, around userforms' own process(). Two things it adds, both answered on the form's page
 * with the visitor's typed text kept (photos must be chosen again: a browser never refills a file input):
 *
 *  1. A limit per visitor address: at most $throttle_max accepted submissions in $throttle_window seconds (3 in 10
 *     minutes). A submission counts once it has passed the form's checks, the reCAPTCHA check included; a refused post
 *     never reaches this method. The count lives in SilverStripe's cache (QuoteFormThrottle, app/_config/userforms.yml),
 *     keyed by a hash of the address.
 *  2. A failed send is an error, not a thank-you. Saving submissions is off (photos and details travel only by email),
 *     and userforms catches every mail error and shows the thank-you anyway, so a failed send would lose the request
 *     with nobody knowing. While process() runs, the site's mailer is wrapped in App\Control\RecordingMailer; if any
 *     email failed, or none was sent at all while saving is off, the visitor is asked to try again or phone, and the
 *     failure is logged (no visitor details in the log).
 *
 * The messages are in app/lang/en.yml. Nothing here decides what a valid submission is: userforms, the field classes
 * and SpamProtectionFieldValidator still do.
 */
class ContactPageController extends UserDefinedFormController
{
    private static int $throttle_max = 3;

    /** Seconds. */
    private static int $throttle_window = 600;

    public function process($data, $form)
    {
        $wait = $this->throttleWait();
        if ($wait > 0) {
            $this->logger()->notice(sprintf(
                'Quote form: a submission was refused by the limit of %d per %d minutes from one address.',
                static::config()->get('throttle_max'),
                (int) ceil(static::config()->get('throttle_window') / 60)
            ));
            $minutes = (int) ceil($wait / 60);
            return $this->backToForm($form, _t(
                self::class . '.THROTTLED',
                'Your quote request has not been sent. Please try again in 1 minute.|Your quote request has not been sent. Please try again in {count} minutes.',
                ['count' => $minutes]
            ));
        }
        $this->recordSubmission();

        $injector = Injector::inst();
        $mailer = $injector->get(MailerInterface::class);
        $recorder = new RecordingMailer($mailer);
        $injector->registerService($recorder, MailerInterface::class);
        try {
            $response = parent::process($data, $form);
        } finally {
            $injector->registerService($mailer, MailerInterface::class);
        }

        $failures = $recorder->failures();
        $nothingSent = $this->DisableSaveSubmissions && $recorder->sentCount() === 0;
        if (!$failures && !$nothingSent) {
            return $response;
        }

        $this->logger()->error(sprintf(
            'Quote form: a quote request was NOT delivered (%d email(s) sent, %d failed%s). The visitor was asked to try '
            . 'again or phone. %s',
            $recorder->sentCount(),
            count($failures),
            $nothingSent && !$failures ? ', no recipient got an email' : '',
            $failures ? 'First error: ' . $failures[0]->getMessage() : 'Check the form\'s Recipients tab.'
        ));
        // process() marked the form as finished; the thank-you page must not open for a request that was not sent.
        $this->getRequest()->getSession()->clear('FormProcessed');
        return $this->backToForm($form, _t(
            self::class . '.SEND_FAILED',
            'Your quote request has not been sent. Something went wrong on our side. Please try again in a few minutes.'
        ));
    }

    /**
     * Back to the form's own page with the message at the top of the form (UserForm.ss prints form-wide messages there,
     * where the #anchor lands) and the visitor's typed text restored, as App\Extensions\UserFormRefusalExtension does
     * for a refused post.
     */
    private function backToForm(Form $form, string $message): HTTPResponse
    {
        $data = $form->getData();
        $hadPhotos = false;
        foreach ($form->Fields()->dataFields() as $field) {
            if ($field instanceof PhotosUploadField) {
                $hadPhotos = $hadPhotos || $field->uploadedFiles();
                unset($data[$field->getName()]);
            }
        }

        $phone = trim((string) SiteConfig::current_site_config()->Phone);
        $message .= ' ' . ($phone !== ''
            ? _t(self::class . '.CALL_US', 'Or call us at {phone}.', ['phone' => $phone])
            : _t(self::class . '.CONTACT_US', 'Or phone or email us instead.'));
        if ($hadPhotos) {
            $message .= ' ' . _t(self::class . '.PHOTOS_AGAIN', 'Please add your photos again.');
        }

        $result = ValidationResult::create();
        $result->addError($message);
        $form->setSessionValidationResult($result);
        $form->setSessionData($data);
        // A fresh response: after a failed send, process() has already pointed this one at the thank-you page.
        $this->setResponse(HTTPResponse::create());
        return $this->redirect(Controller::join_links($this->Link(), '#' . $form->FormName()));
    }

    /** Seconds until this address may submit again; 0 when it may submit now. */
    private function throttleWait(): int
    {
        $recent = $this->recentSubmissions();
        if (count($recent) < (int) static::config()->get('throttle_max')) {
            return 0;
        }
        return max(1, min($recent) + (int) static::config()->get('throttle_window') - time());
    }

    private function recordSubmission(): void
    {
        $recent = $this->recentSubmissions();
        $recent[] = time();
        $this->throttleCache()->set($this->throttleKey(), $recent, (int) static::config()->get('throttle_window'));
    }

    /** @return int[] times of this address's accepted submissions inside the window */
    private function recentSubmissions(): array
    {
        $cutoff = time() - (int) static::config()->get('throttle_window');
        $times = $this->throttleCache()->get($this->throttleKey(), []);
        return array_values(array_filter(is_array($times) ? $times : [], fn ($t) => (int) $t > $cutoff));
    }

    private function throttleKey(): string
    {
        return 'ip' . sha1((string) $this->getRequest()->getIP());
    }

    private function throttleCache(): CacheInterface
    {
        return Injector::inst()->get(CacheInterface::class . '.QuoteFormThrottle');
    }

    private function logger(): LoggerInterface
    {
        return Injector::inst()->get(LoggerInterface::class);
    }
}
