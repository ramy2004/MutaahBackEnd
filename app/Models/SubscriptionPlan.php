<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'SubscriptionPlans'; // مطابقة اسم الجدول في الـ Migration[cite: 1]

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id ??= (string) Str::uuid();
        });
    }

    protected $fillable = [
        'id',
        'plan_type',
        'price',
        'max_listings_per_month',
        'max_rentals_per_month',
        'listings_count_this_month',
        'rentals_count_this_month',
        'commission_rate',
        'has_detailed_reports',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'has_detailed_reports' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
