<?php

namespace App\Services;

use App\Models\CoursePayment;

class SePayService
{
    public static function getPattern(): string
    {
        $pattern = SettingsService::get('sepay.pattern', config('sepay.pattern', 'SE'));
        $pattern = is_string($pattern) ? trim($pattern) : 'SE';

        return $pattern !== '' ? $pattern : 'SE';
    }

    public static function getWebhookToken(): ?string
    {
        $token = SettingsService::get('sepay.webhook_token', config('sepay.webhook_token'));

        return $token !== null && $token !== '' ? (string) $token : null;
    }

    public static function getBankInfo(): array
    {
        return [
            'code' => SettingsService::get('sepay_gateway.bank_code', config('sepay_gateway.bank_code')),
            'name' => SettingsService::get('sepay_gateway.bank_name', config('sepay_gateway.bank_name')),
            'account_number' => SettingsService::get('sepay_gateway.account_number', config('sepay_gateway.account_number')),
            'account_name' => SettingsService::get('sepay_gateway.account_name', config('sepay_gateway.account_name')),
        ];
    }

    public static function buildTransferCode(CoursePayment $payment): string
    {
        return self::getPattern() . $payment->id;
    }

    public static function ensureOrderCode(CoursePayment $payment): string
    {
        $orderCode = self::buildTransferCode($payment);
        if ($payment->order_code !== $orderCode) {
            $payment->order_code = $orderCode;
            $payment->save();
        }

        return $orderCode;
    }

    public static function buildQrUrl(CoursePayment $payment): ?string
    {
        $bank = self::getBankInfo();
        $bankCode = $bank['code'] ?? null;
        $accountNumber = $bank['account_number'] ?? null;

        if (!$bankCode || !$accountNumber) {
            return null;
        }

        $qs = http_build_query([
            'acc' => $accountNumber,
            'bank' => $bankCode,
            'amount' => (int) $payment->amount,
            'des' => self::ensureOrderCode($payment),
            'template' => 'compact',
        ]);

        return 'https://qr.sepay.vn/img?' . $qs;
    }
}
