<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SavedItem extends Model
{
    use HasFactory;

    protected $table = 'saved_items';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'product_id',
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

    // علاقة العنصر المحفوظ بالمستخدم الذي قام بحفظه
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // علاقة العنصر المحفوظ بالمنتج المرتبط به
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
