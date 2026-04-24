<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class MailConfigService
{
    /**
     * Apply SMTP settings from database to the running config,
     * then purge the cached mailer so the next send uses the new config.
     */
    public static function applyFromSettings(): void
    {
        $mailer    = SettingsService::get('mail.mailer');
        $host      = SettingsService::get('mail.host');
        $port      = SettingsService::get('mail.port');
        $username  = SettingsService::get('mail.username');
        $password  = SettingsService::get('mail.password');
        $encryption = SettingsService::get('mail.encryption');
        $fromAddr  = SettingsService::get('mail.from_address');
        $fromName  = SettingsService::get('mail.from_name');

        // Only override if values exist in DB
        if ($mailer)     Config::set('mail.default', $mailer);
        if ($host)       Config::set('mail.mailers.smtp.host', $host);
        if ($port)       Config::set('mail.mailers.smtp.port', (int) $port);
        if ($username)   Config::set('mail.mailers.smtp.username', $username);
        if ($password)   Config::set('mail.mailers.smtp.password', $password);
        if ($encryption) {
            Config::set('mail.mailers.smtp.encryption', $encryption === 'null' ? null : $encryption);
        }
        if ($fromAddr)   Config::set('mail.from.address', $fromAddr);
        if ($fromName)   Config::set('mail.from.name', $fromName);

        // Purge cached mailer so next Mail::send() rebuilds the transport
        Mail::purge('smtp');
    }
}
