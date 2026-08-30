<?php

declare(strict_types=1);

/**
 * Minimal mail sender using PHP's built-in mail() (routes through the
 * server's local MTA). Reliable enough on shared/cPanel hosting as long as
 * "From" uses a real address on this hosting account's own domain, since
 * that's what SPF/DKIM cover — an unrelated "From" domain is likely to be
 * marked as spam or rejected outright.
 */
final class Mailer
{
    public static function send(array $mailConfig, string $to, string $subject, string $body): bool
    {
        $fromAddress = $mailConfig['from'] ?? '';
        $fromName = $mailConfig['app_name'] ?? 'Portal';

        if ($fromAddress === '') {
            return false;
        }

        $headers = "From: {$fromName} <{$fromAddress}>\r\n"
            . "Reply-To: {$fromAddress}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . 'X-Mailer: PHP/' . phpversion();

        // Force the envelope sender (SMTP MAIL FROM / Return-Path) to match
        // the configured From address. Without this, sendmail picks a
        // server-assigned default that can differ per subdomain/vhost and
        // silently fail SPF even though the visible From header is correct.
        return mail($to, $subject, $body, $headers, '-f' . $fromAddress);
    }
}
