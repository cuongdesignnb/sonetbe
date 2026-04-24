<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_id',
        'company_name',
        'tax_code',
        'company_address',
        'invoice_email',
        'status',
        'provider',
        'provider_invoice_id',
        'provider_invoice_key',
        'invoice_number',
        'invoice_series',
        'provider_status',
        'provider_request_payload',
        'provider_response_payload',
        'last_error',
        'retry_count',
        'processing_started_at',
        'processing_completed_at',
        'admin_note',
    ];

    protected $casts = [
        'provider_request_payload' => 'array',
        'provider_response_payload' => 'array',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(CoursePayment::class, 'payment_id');
    }
}
