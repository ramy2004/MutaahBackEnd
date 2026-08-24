<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use App\Models\SubscriptionPlan;
use App\Models\Product;

class User extends Authenticatable implements CanResetPasswordContract
{
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword;

    // تحديد اسم الجدول ليطابق المايجريشن
    protected $table = 'Users';

    // إيقاف الـ Auto Increment للـ ID لأننا نستخدم UUID
    public $incrementing = false;
    protected $keyType = 'string';

    // السماح بإدخال الحقول المطلوبة
    protected $fillable = [
        'id',
        'full_name',
        'username',
        'email',
        'phone',
        'password_hash',
        'avatar',
        'governorate',
        'district',
        'email_verified',
        'user_status',
        'role',
        'plan_id',
        'listings_used_this_month',
        'rentals_used_this_month',
        'usage_reset_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    // إنشاء UUID تلقائياً قبل حفظ أي مستخدم جديد
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // إخفاء كلمة المرور واستخدام password_hash للمصادقة
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // علاقة المنتجات المملوكة للمستخدم
    public function products()
    {
        return $this->hasMany(Product::class, 'owner_id');
    }

    // علاقة خطة الاشتراك (ربط plan_id)
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    // علاقة العناصر المحفوظة (المفضلة) الخاصة بالمستخدم
    public function savedItems()
    {
        return $this->hasMany(SavedItem::class, 'user_id');
    }

    public function rentalRequests()
    {
        return $this->hasMany(RentalRequest::class, 'renter_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function identityVerifications()
    {
        return $this->hasMany(IdentityVerification::class, 'user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
