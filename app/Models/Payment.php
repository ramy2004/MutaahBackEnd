<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'rental_id',
        'payer_id',
        'price_snapshot',
        'rental_price_total',
        'deposit_amount',
        'grand_total',
        'receipt_image',
        'payment_status',
        'cancellation_fee',
        'refund_amount',
        'refund_status',
        'paid_at',
    ];

    protected $casts = [
        'price_snapshot' => 'decimal:2',
        'rental_price_total' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'paid_at' => 'datetime',
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

    public function rental()
    {
        return $this->belongsTo(RentalRequest::class, 'rental_id');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }
}
