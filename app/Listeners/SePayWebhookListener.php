<?php

namespace App\Listeners;

use SePay\SePay\Events\SePayWebhookEvent;

class SePayWebhookListener
{
    /**
     * Deprecated: webhook handling is now done in SePayWebhookController.
     */
    public function handle(SePayWebhookEvent $event): void
    {
        // No-op (kept only to avoid breaking legacy wiring).
    }
}
