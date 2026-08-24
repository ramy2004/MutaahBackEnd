<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // معالجة الصور (سواء كانت مصفوفة أو JSON)
        $images = is_array($this->product_images) ? $this->product_images : json_decode($this->product_images, true);

        return [
            // البيانات الأساسية (للـ Index والـ Show)
            'id'             => $this->id,
            'title'          => $this->title,
            'category'       => $this->category,
            'price_per_hour' => (float) $this->price_per_hour,
            'primary_image'  => !empty($images) ? $images[0] : null,
            'status'         => $this->status,
            'is_available'   => $this->status === 'active',

            // تفاصيل إضافية للـ Show (لكنها لا تضر الـ Index)
            'description'    => $this->description,
            'deposit_amount' => (float) $this->deposit_amount,
            'product_images' => $images, // هنا نرسل كل الصور للـ Gallery في صفحة الـ Show
            'available_dates' => $this->available_dates ?? [],
            'start_time'     => $this->start_time,
            'end_time'       => $this->end_time,
            'is_all_day'     => (bool) $this->is_all_day,

            'location'       => [
                'governorate' => $this->owner->governorate ?? null,
                'district'    => $this->owner->district ?? null,
            ],

            'owner'          => [
                'id'          => $this->owner->id ?? null,
                'full_name'   => $this->owner->full_name ?? null,
                'is_verified' => (bool) ($this->owner->email_verified ?? false), // إضافة حالة التوثيق كما في الصورة
            ],
        ];
    }
}
