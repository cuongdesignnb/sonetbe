<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount',
        'usage_limit',
        'used_count',
        'usage_per_user',
        'valid_from',
        'valid_until',
        'status',
        'applicable_type',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'usage_per_user' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    // Relationships
    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function coursePayments()
    {
        return $this->hasMany(CoursePayment::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'voucher_courses')->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeValid($query)
    {
        $now = Carbon::now();
        return $query->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereRaw('used_count < usage_limit');
            });
    }

    // Accessors
    public function getIsValidAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = Carbon::now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function getRemainingUsesAttribute(): ?int
    {
        if ($this->usage_limit === null) {
            return null;
        }
        return max(0, $this->usage_limit - $this->used_count);
    }

    // Methods
    public function canBeUsedByUser(int $userId): bool
    {
        if (!$this->is_valid) {
            return false;
        }

        if ($this->usage_per_user !== null) {
            $userUsageCount = $this->usages()->where('user_id', $userId)->count();
            if ($userUsageCount >= $this->usage_per_user) {
                return false;
            }
        }

        return true;
    }

    public function isApplicableToCourse(int $courseId): bool
    {
        if ($this->applicable_type !== 'specific') {
            return true; // 'all' type applies to everything
        }

        return $this->courses()->where('courses.id', $courseId)->exists();
    }

    public function calculateDiscount(float $amount): float
    {
        if ($amount < $this->min_order_amount) {
            return 0;
        }

        if ($this->discount_type === 'fixed') {
            $discount = min($this->discount_value, $amount);
        } else {
            // percent
            $discount = $amount * ($this->discount_value / 100);
            if ($this->max_discount !== null) {
                $discount = min($discount, $this->max_discount);
            }
        }

        return round($discount, 2);
    }

    public function getValidationError(float $orderAmount, int $userId, ?int $courseId = null): ?string
    {
        if ($this->status !== 'active') {
            return 'Mã giảm giá không hoạt động';
        }

        $now = Carbon::now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return 'Mã giảm giá chưa có hiệu lực';
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return 'Mã giảm giá đã hết hạn';
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'Mã giảm giá đã hết lượt sử dụng';
        }

        if ($orderAmount < $this->min_order_amount) {
            return 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($this->min_order_amount) . 'đ';
        }

        if ($this->usage_per_user !== null) {
            $userUsageCount = $this->usages()->where('user_id', $userId)->count();
            if ($userUsageCount >= $this->usage_per_user) {
                return 'Bạn đã sử dụng hết lượt dùng mã này';
            }
        }

        // Check course applicability if courseId provided
        if ($courseId !== null && !$this->isApplicableToCourse($courseId)) {
            return 'Mã giảm giá không áp dụng cho khóa học này';
        }

        return null;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))->first();
    }
}
