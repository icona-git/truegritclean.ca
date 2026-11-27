<?php

namespace App\Control;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Throwable;

/**
 * Wraps the site's mailer for one quote submission and counts what happened: emails sent and emails that failed.
 * Sending itself is unchanged (same mailer, same transport); a failure is still thrown to the caller.
 *
 * userforms catches and logs every send error and then shows the thank-you, so without this the quote form could not
 * tell a visitor that their request did not go out (App\Pages\ContactPageController).
 */
class RecordingMailer implements MailerInterface
{
    private int $sent = 0;

    /** @var Throwable[] */
    private array $failures = [];

    public function __construct(private readonly MailerInterface $inner)
    {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        try {
            $this->inner->send($message, $envelope);
        } catch (Throwable $e) {
            $this->failures[] = $e;
            throw $e;
        }
        $this->sent++;
    }

    public function sentCount(): int
    {
        return $this->sent;
    }

    /** @return Throwable[] */
    public function failures(): array
    {
        return $this->failures;
    }

    public function inner(): MailerInterface
    {
        return $this->inner;
    }
}
