<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $table = 'Products';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'owner_id',
        'title',
        'description',
        'category',
        'product_images',
        'available_dates',
        'start_time',
        'end_time',
        'is_all_day',
        'price_per_hour',
        'deposit_amount',
        'status',
    ];

    protected $casts = [
        'product_images'  => 'array',
        'available_dates' => 'array',
        'is_all_day'      => 'boolean',
        'price_per_hour'  => 'decimal:2',
        'deposit_amount'  => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // علاقة المنتج بصاحبه (المستخدم)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function rentalRequests()
    {
        return $this->hasMany(RentalRequest::class, 'product_id');
    }
}
