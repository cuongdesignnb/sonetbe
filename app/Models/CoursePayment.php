<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type',
        'user_id',
        'course_id',
        'ebook_id',
        'webinar_id',
        'enrollment_id',
        'order_code',
        'voucher_id',
        'discount_amount',
        'original_amount',
        'amount',
        'status',
        'sepay_txn_id',
        'transfer_amount',
        'raw_payload',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'transfer_amount' => 'decimal:2',
        'raw_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function ebook()
    {
        return $this->belongsTo(Ebook::class);
    }

    public function webinar()
    {
        return $this->belongsTo(Webinar::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function invoiceRequest()
    {
        return $this->hasOne(InvoiceRequest::class, 'payment_id');
    }
}
