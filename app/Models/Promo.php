<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $fillable = [
        'name',
        'code',
        'discount_type',
        'discount_value',
        'min_package_price',
        'is_active',
        'valid_from',
        'valid_until',
        'description',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        $today = now()->startOfDay();
        if ($this->valid_from && $today->lt($this->valid_from)) return false;
        if ($this->valid_until && $today->gt($this->valid_until)) return false;
        return true;
    }

    public function calculateDiscount(float $price): float
    {
        if ($this->discount_type === 'percentage') {
            return round($price * ($this->discount_value / 100), 2);
        }
        return min((float) $this->discount_value, $price);
    }

    public function getDiscountLabelAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return 'Diskon ' . $this->discount_value . '%';
        }
        return 'Potongan Rp ' . number_format($this->discount_value, 0, ',', '.');
    }
}
