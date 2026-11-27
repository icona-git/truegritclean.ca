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
 *  1. Two limits. Per visitor address: at most $throttle_max accepted submissions in $throttle_window seconds (3 in 10
 *     minutes). For the whole site: at most $site_max in $site_window seconds (20 in an hour), so many addresses
 *     together cannot fill the firm's inbox with photo emails either; a real day's quote requests are far below it.
 *     A submission counts once it has passed the form's checks, the reCAPTCHA check included; a refused post never
 *     reaches this method. The counts live in SilverStripe's cache (QuoteFormThrottle, app/_config/userforms.yml),
 *     the visitor's keyed by a hash of the address. Reading and adding to a count are two steps, so requests landing
 *     in the same instant can pass one or two over a limit: these are brakes, not exact counters.
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

    private static int $site_max = 20;

    /** Seconds. */
    private static int $site_window = 3600;

    private const SITE_KEY = 'site';

    public function process($data, $form)
    {
        $siteMax = (int) static::config()->get('site_max');
        $siteWindow = (int) static::config()->get('site_window');
        $wait = $this->throttleWait();
        if ($wait === 0 && ($siteWait = $this->waitFor(self::SITE_KEY, $siteMax, $siteWindow)) > 0) {
            // An error, not a notice: this is either an attack or a limit set too low, and someone should look.
            $this->logger()->error(sprintf(
                'Quote form: a submission was refused by the site-wide limit of %d per %d minutes. If these are real '
                . 'requests, raise App\\Pages\\ContactPageController.site_max.',
                $siteMax,
                (int) ceil($siteWindow / 60)
            ));
            return $this->backToForm($form, _t(
                self::class . '.SITE_LIMIT',
                'Your quote request has not been sent. We are receiving more requests than usual right now. Please try again in 1 minute.|Your quote request has not been sent. We are receiving more requests than usual right now. Please try again in {count} minutes.',
                ['count' => (int) ceil($siteWait / 60)]
            ));
        }
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
        return $this->waitFor(
            $this->throttleKey(),
            (int) static::config()->get('throttle_max'),
            (int) static::config()->get('throttle_window')
        );
    }

    /** Seconds until the count under $key is back below $max; 0 when it is below now. */
    private function waitFor(string $key, int $max, int $window): int
    {
        $recent = $this->recentSubmissions($key, $window);
        if (count($recent) < $max) {
            return 0;
        }
        return max(1, min($recent) + $window - time());
    }

    /** Counts this accepted submission for the visitor's address and for the site. */
    private function recordSubmission(): void
    {
        $counts = [
            $this->throttleKey() => (int) static::config()->get('throttle_window'),
            self::SITE_KEY => (int) static::config()->get('site_window'),
        ];
        foreach ($counts as $key => $window) {
            $recent = $this->recentSubmissions($key, $window);
            $recent[] = time();
            $this->throttleCache()->set($key, $recent, $window);
        }
    }

    /** @return int[] times of the accepted submissions counted under $key inside its window */
    private function recentSubmissions(string $key, int $window): array
    {
        $cutoff = time() - $window;
        $times = $this->throttleCache()->get($key, []);
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
