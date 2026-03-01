<?php

declare(strict_types=1);

namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

/**
 * Mailer — Thin static wrapper around PHPMailer for transactional emails.
 *
 * All SMTP credentials are read from environment variables populated by
 * phpdotenv in config/app.php.  Required .env keys:
 *
 *   MAIL_HOST            — SMTP server hostname (e.g. smtp.mailgun.org)
 *   MAIL_PORT            — SMTP port (587 for STARTTLS, 465 for SSL)
 *   MAIL_USER            — SMTP username / API key
 *   MAIL_PASS            — SMTP password / API secret
 *   MAIL_FROM_ADDRESS    — From address (e.g. no-reply@kinarastore.com)
 *   MAIL_FROM_NAME       — From display name (e.g. Kinara Store Hub)
 *   MAIL_ENCRYPTION      — 'tls' (STARTTLS, default) or 'ssl'
 *
 * Design decisions:
 *  - Static methods only — no instantiation required by callers.
 *  - Never throws to the caller — all exceptions are caught internally;
 *    failure returns false and writes to the PHP error log.
 *  - PHPMailer exceptions are enabled internally so that detailed SMTP
 *    diagnostics appear in the log.
 *  - HTML bodies use inline styles (no external CSS) for maximum email
 *    client compatibility.
 */
class Mailer
{
    // -----------------------------------------------------------------------
    // Public transactional email methods
    // -----------------------------------------------------------------------

    /**
     * Send an account verification email to a newly registered store owner.
     *
     * Contains a branded HTML body with a prominent "Verify My Account" button
     * linking to $verifyUrl, plus a plain-text fallback for non-HTML clients.
     *
     * @param string $toEmail   Recipient email address.
     * @param string $toName    Recipient display name (e.g. "Priya Sharma").
     * @param string $verifyUrl Signed verification URL from the registration flow.
     *
     * @return bool True if the message was accepted by the SMTP server, false on failure.
     */
    public static function sendVerificationEmail(
        string $toEmail,
        string $toName,
        string $verifyUrl
    ): bool {
        try {
            $mail = self::configure();

            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Verify your Kinara Store Hub account';

            // --- HTML body ---
            $safeName    = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeUrl     = htmlspecialchars($verifyUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $appUrl      = defined('APP_URL') ? APP_URL : ($_ENV['APP_URL'] ?? 'http://localhost/kinarahub');
            $fromName    = $_ENV['MAIL_FROM_NAME'] ?? 'Kinara Store Hub';
            $safeFromName = htmlspecialchars($fromName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $mail->Body = <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Verify your account</title>
            </head>
            <body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Arial,sans-serif;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f5;padding:40px 0;">
                    <tr>
                        <td align="center">
                            <table width="600" cellpadding="0" cellspacing="0" border="0"
                                   style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                                <!-- Header -->
                                <tr>
                                    <td style="background-color:#1d4ed8;padding:32px 40px;text-align:center;">
                                        <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;letter-spacing:-0.5px;">
                                            {$safeFromName}
                                        </h1>
                                    </td>
                                </tr>
                                <!-- Body -->
                                <tr>
                                    <td style="padding:40px;">
                                        <p style="margin:0 0 16px;color:#374151;font-size:16px;line-height:1.6;">
                                            Hello {$safeName},
                                        </p>
                                        <p style="margin:0 0 16px;color:#374151;font-size:16px;line-height:1.6;">
                                            Thank you for registering with <strong>{$safeFromName}</strong>.
                                            Please verify your email address to activate your store account.
                                        </p>
                                        <p style="margin:0 0 32px;color:#374151;font-size:16px;line-height:1.6;">
                                            This link will expire in <strong>24 hours</strong>.
                                        </p>
                                        <!-- CTA button -->
                                        <table cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="border-radius:6px;background-color:#1d4ed8;">
                                                    <a href="{$safeUrl}"
                                                       target="_blank"
                                                       style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:16px;font-weight:600;text-decoration:none;border-radius:6px;">
                                                        Verify My Account
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin:32px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">
                                            If the button does not work, copy and paste this URL into your browser:
                                        </p>
                                        <p style="margin:8px 0 0;word-break:break-all;">
                                            <a href="{$safeUrl}" style="color:#1d4ed8;font-size:13px;">{$safeUrl}</a>
                                        </p>
                                        <p style="margin:24px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">
                                            If you did not create an account, you can safely ignore this email.
                                        </p>
                                    </td>
                                </tr>
                                <!-- Footer -->
                                <tr>
                                    <td style="background-color:#f9fafb;padding:24px 40px;text-align:center;border-top:1px solid #e5e7eb;">
                                        <p style="margin:0;color:#9ca3af;font-size:12px;">
                                            &copy; {$safeFromName} &mdash; All rights reserved.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            HTML;

            // --- Plain-text fallback ---
            $mail->AltBody = "Hello {$toName},\n\n"
                . "Thank you for registering with {$fromName}.\n\n"
                . "Please verify your email address by visiting the link below:\n"
                . "{$verifyUrl}\n\n"
                . "This link will expire in 24 hours.\n\n"
                . "If you did not create an account, you can safely ignore this email.\n\n"
                . "-- {$fromName}";

            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('[Mailer] sendVerificationEmail failed for ' . $toEmail . ': ' . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            error_log('[Mailer] Unexpected error in sendVerificationEmail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a password reset email containing a time-limited reset link.
     *
     * The $resetUrl should be a signed URL generated by the auth controller
     * (e.g. /reset-password?token=<hmac-signed-token>&expires=<timestamp>).
     * This method only sends the email — it does not generate or validate tokens.
     *
     * @param string $toEmail  Recipient email address.
     * @param string $toName   Recipient display name.
     * @param string $resetUrl Signed password reset URL (typically valid for 1 hour).
     *
     * @return bool True if the message was accepted by the SMTP server, false on failure.
     */
    public static function sendPasswordReset(
        string $toEmail,
        string $toName,
        string $resetUrl
    ): bool {
        try {
            $mail = self::configure();

            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Reset your Kinara Store Hub password';

            $safeName    = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeUrl     = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $fromName    = $_ENV['MAIL_FROM_NAME'] ?? 'Kinara Store Hub';
            $safeFromName = htmlspecialchars($fromName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $mail->Body = <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Reset your password</title>
            </head>
            <body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Arial,sans-serif;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f5;padding:40px 0;">
                    <tr>
                        <td align="center">
                            <table width="600" cellpadding="0" cellspacing="0" border="0"
                                   style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                                <!-- Header -->
                                <tr>
                                    <td style="background-color:#1d4ed8;padding:32px 40px;text-align:center;">
                                        <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;letter-spacing:-0.5px;">
                                            {$safeFromName}
                                        </h1>
                                    </td>
                                </tr>
                                <!-- Body -->
                                <tr>
                                    <td style="padding:40px;">
                                        <p style="margin:0 0 16px;color:#374151;font-size:16px;line-height:1.6;">
                                            Hello {$safeName},
                                        </p>
                                        <p style="margin:0 0 16px;color:#374151;font-size:16px;line-height:1.6;">
                                            We received a request to reset the password for your
                                            <strong>{$safeFromName}</strong> account.
                                        </p>
                                        <p style="margin:0 0 32px;color:#374151;font-size:16px;line-height:1.6;">
                                            Click the button below to choose a new password.
                                            This link will expire in <strong>1 hour</strong>.
                                        </p>
                                        <!-- CTA button -->
                                        <table cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="border-radius:6px;background-color:#dc2626;">
                                                    <a href="{$safeUrl}"
                                                       target="_blank"
                                                       style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:16px;font-weight:600;text-decoration:none;border-radius:6px;">
                                                        Reset My Password
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin:32px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">
                                            If the button does not work, copy and paste this URL into your browser:
                                        </p>
                                        <p style="margin:8px 0 0;word-break:break-all;">
                                            <a href="{$safeUrl}" style="color:#1d4ed8;font-size:13px;">{$safeUrl}</a>
                                        </p>
                                        <p style="margin:24px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">
                                            If you did not request a password reset, please ignore this email.
                                            Your password will not be changed.
                                        </p>
                                    </td>
                                </tr>
                                <!-- Footer -->
                                <tr>
                                    <td style="background-color:#f9fafb;padding:24px 40px;text-align:center;border-top:1px solid #e5e7eb;">
                                        <p style="margin:0;color:#9ca3af;font-size:12px;">
                                            &copy; {$safeFromName} &mdash; All rights reserved.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            HTML;

            // --- Plain-text fallback ---
            $mail->AltBody = "Hello {$toName},\n\n"
                . "We received a request to reset your {$fromName} account password.\n\n"
                . "Visit the link below to reset your password (valid for 1 hour):\n"
                . "{$resetUrl}\n\n"
                . "If you did not request a password reset, please ignore this email.\n\n"
                . "-- {$fromName}";

            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('[Mailer] sendPasswordReset failed for ' . $toEmail . ': ' . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            error_log('[Mailer] Unexpected error in sendPasswordReset: ' . $e->getMessage());
            return false;
        }
    }

    // -----------------------------------------------------------------------
    // Private factory
    // -----------------------------------------------------------------------

    /**
     * Build and return a configured PHPMailer instance.
     *
     * Reads SMTP settings from environment variables.  Throws a MailerException
     * (which the public methods catch) if the configuration is invalid.
     *
     * Environment variables consumed:
     *   MAIL_HOST            — SMTP hostname
     *   MAIL_PORT            — SMTP port (default: 587)
     *   MAIL_USER            — SMTP username
     *   MAIL_PASS            — SMTP password
     *   MAIL_FROM_ADDRESS    — Envelope From address
     *   MAIL_FROM_NAME       — Envelope From display name
     *   MAIL_ENCRYPTION      — 'tls' (STARTTLS) or 'ssl' (default: 'tls')
     *
     * @return PHPMailer A ready-to-use PHPMailer instance (not yet sent).
     *
     * @throws MailerException If PHPMailer configuration fails.
     */
    private static function configure(): PHPMailer
    {
        // Pass true to enable PHPMailer exceptions for detailed SMTP diagnostics.
        $mail = new PHPMailer(true);

        // --- SMTP transport ---
        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host     = $_ENV['MAIL_HOST']     ?? 'localhost';
        $mail->Port     = (int) ($_ENV['MAIL_PORT'] ?? 587);
        $mail->Username = $_ENV['MAIL_USER']     ?? '';
        $mail->Password = $_ENV['MAIL_PASS']     ?? '';

        // Encryption: 'tls' → STARTTLS (port 587), 'ssl' → SMTPS (port 465).
        $encryption = strtolower($_ENV['MAIL_ENCRYPTION'] ?? 'tls');

        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            // Default to STARTTLS (recommended for port 587).
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // --- From address ---
        $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@kinarastore.com';
        $fromName    = $_ENV['MAIL_FROM_NAME']    ?? 'Kinara Store Hub';

        $mail->setFrom($fromAddress, $fromName);
        $mail->isHTML(true);
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        return $mail;
    }
}
