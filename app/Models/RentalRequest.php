<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Payment;

class RentalRequest extends Model
{
    use HasFactory;

    protected $table = 'rental_requests';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'renter_id',
        'product_id',
        'start_time',
        'end_time',
        'owner_status',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'cancellation_fee',
        'refund_amount',
        'refund_status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancellation_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
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

    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'rental_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
