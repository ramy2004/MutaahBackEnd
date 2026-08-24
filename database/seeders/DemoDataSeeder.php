<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. جلب أو إنشاء خطة اشتراك
        $plan = SubscriptionPlan::first();
        $planId = $plan ? $plan->id : null;

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'full_name' => 'Mutah Administrator',
                'email' => 'admin@mutah.test',
                'phone' => '0599000000',
                'password_hash' => Hash::make('Admin@123456'),
                'governorate' => 'gaza',
                'district' => 'Administration',
                'email_verified' => true,
                'user_status' => 'active',
                'role' => 'admin',
                'plan_id' => $planId,
            ]
        );

        // 2. إنشاء / جلب 5 مستخدمين ببيانات واقعية بدون تكرار
        $usersData = [
            [
                'full_name'   => 'أحمد سالم',
                'username'    => 'ahmed_salem',
                'email'       => 'ahmed@demo.com',
                'phone'       => '0599111111',
                'governorate' => 'gaza',
                'district'    => 'الرمال',
            ],
            [
                'full_name'   => 'محمد النجار',
                'username'    => 'mohammed_n',
                'email'       => 'mohammed@demo.com',
                'phone'       => '0599222222',
                'governorate' => 'gaza',
                'district'    => 'النصر',
            ],
            [
                'full_name'   => 'محمود المدهون',
                'username'    => 'mahmoud_m',
                'email'       => 'mahmoud@demo.com',
                'phone'       => '0599333333',
                'governorate' => 'gaza',
                'district'    => 'الشجاعية',
            ],
            [
                'full_name'   => 'خالد أبو العوف',
                'username'    => 'khaled_a',
                'email'       => 'khaled@demo.com',
                'phone'       => '0599444444',
                'governorate' => 'gaza',
                'district'    => 'جباليا',
            ],
            [
                'full_name'   => 'عمر رضوان',
                'username'    => 'omar_r',
                'email'       => 'omar@demo.com',
                'phone'       => '0599555555',
                'governorate' => 'gaza',
                'district'    => 'الشيخ رضوان',
            ],
        ];

        $createdUsers = [];
        foreach ($usersData as $userData) {
            $createdUsers[] = User::firstOrCreate(
                ['username' => $userData['username']],
                array_merge($userData, [
                    'password_hash'  => Hash::make('12345678'),
                    'email_verified' => true,
                    'user_status'    => 'active',
                    'role'           => 'user',
                    'plan_id'        => $planId,
                ])
            );
        }

        // 3. قائمة 15 منتجاً حقيقياً
        $productsData = [
            [
                'title'          => 'كاميرا سوني A7 III',
                'category'       => 'cameras',
                'description'    => 'كاميرا احترافية للتصوير الفوتوغرافي والفيديو مع عدسة 24-70mm',
                'price_per_hour' => 25,
                'deposit_amount' => 300,
                'product_images' => ['https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'مولد كهرباء 5KW',
                'category'       => 'items',
                'description'    => 'مولد بنزين كاتم للصوت يعمل بكفاءة عالية لتشغيل المنازل',
                'price_per_hour' => 40,
                'deposit_amount' => 500,
                'product_images' => ['https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'مثقاب بوش كهربائي',
                'category'       => 'items',
                'description'    => 'دريل بوش احترافي مع طقم ريش شامل لأعمال الخرسانة والخشب',
                'price_per_hour' => 10,
                'deposit_amount' => 100,
                'product_images' => ['https://images.unsplash.com/photo-1504148455328-c376907d081c?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'لابتوب ديل XPS 15',
                'category'       => 'electronics',
                'description'    => 'معالج i7، رام 32GB، كرت شاشة RTX لتصميم الجرافيك والمونتاج',
                'price_per_hour' => 30,
                'deposit_amount' => 400,
                'product_images' => ['https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'سيارة هيونداي أكسنت 2022',
                'category'       => 'house items',
                'description'    => 'سيارة أوتوماتيك حديثة واقتصادية في البنزين، جاهزة للتنقلات',
                'price_per_hour' => 50,
                'deposit_amount' => 600,
                'product_images' => ['https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'آيفون 14 بروماكس 256GB',
                'category'       => 'electronics',
                'description'    => 'جهاز بحالة الزيرو لتصوير الفعاليات والمناسبات بوضوح 4K',
                'price_per_hour' => 20,
                'deposit_amount' => 350,
                'product_images' => ['https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'لوح طاقة شمسية 550 واط',
                'category'       => 'items',
                'description'    => 'ألواح مونو كريستال عالية الكفاءة للمخيمات أو حالات الطوارئ',
                'price_per_hour' => 15,
                'deposit_amount' => 200,
                'product_images' => ['https://images.unsplash.com/photo-1509391365360-2e959784a276?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'جهاز قياس ضغط الدم رقمي',
                'category'       => 'medical items',
                'description'    => 'جهاز أومرون دقيق جداً وسهل الاستخدام مع شاشة رقمية',
                'price_per_hour' => 5,
                'deposit_amount' => 50,
                'product_images' => ['https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'طائرة درون DJI Mini 3',
                'category'       => 'cameras',
                'description'    => 'درون خفيفة للتصوير الجوي بجودة عالية مع رموت كنترول وشاشة',
                'price_per_hour' => 70,
                'deposit_amount' => 800,
                'product_images' => ['https://images.unsplash.com/photo-1527977966376-1c8408f9f108?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'شاشة سامسونج 55 بوصة سمارت',
                'category'       => 'electronics',
                'description'    => 'شاشة 4K Curved ممتازة للعروض والمباريات والألعاب',
                'price_per_hour' => 35,
                'deposit_amount' => 400,
                'product_images' => ['https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'طابعة ليزر HP',
                'category'       => 'electronics',
                'description'    => 'طابعة سريعة أبيض وأسود لطباعة المستندات والأبحاث بكميات كبيرة',
                'price_per_hour' => 12,
                'deposit_amount' => 150,
                'product_images' => ['https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'ميكروسكوب ديجيتال إلكتروني',
                'category'       => 'medical items',
                'description'    => 'ميكروسكوب بشاشة تكبير لإصلاح البوردات والقطع الإلكترونية',
                'price_per_hour' => 20,
                'deposit_amount' => 200,
                'product_images' => ['https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'عدسة كانون 50mm f/1.8',
                'category'       => 'cameras',
                'description'    => 'عدسة بورتريه ممتازة مع عزل قوي للصور الفوتوغرافية',
                'price_per_hour' => 15,
                'deposit_amount' => 150,
                'product_images' => ['https://images.unsplash.com/photo-1617005082133-548c4dd27f35?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'خيمة تخييم تتسع لـ 4 أشخاص',
                'category'       => 'camping',
                'description'    => 'خيمة مقاومة للماء والريح ممتازة للرحلات الشاطئية والخارجية',
                'price_per_hour' => 18,
                'deposit_amount' => 120,
                'product_images' => ['https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80'],
            ],
            [
                'title'          => 'جيتار ياماها كلاسيك',
                'category'       => 'instruments',
                'description'    => 'آلة جيتار خشبية بحالة ممتازة ومعايرة للتسجيل والصوت النقي',
                'price_per_hour' => 15,
                'deposit_amount' => 150,
                'product_images' => ['https://images.unsplash.com/photo-1510915361894-db8b60106cb1?auto=format&fit=crop&w=800&q=80'],
            ],
        ];

        // إضافة المنتجات
        foreach ($productsData as $index => $prod) {
            $user = $createdUsers[$index % count($createdUsers)];

            Product::firstOrCreate(
                ['title' => $prod['title']],
                [
                    'owner_id'        => $user->id,
                    'category'        => $prod['category'],
                    'description'     => $prod['description'],
                    'price_per_hour'  => $prod['price_per_hour'],
                    'deposit_amount'  => $prod['deposit_amount'],
                    'product_images'  => $prod['product_images'],
                    'available_dates' => ['2026-08-20', '2026-08-21', '2026-08-22'],
                    'start_time'      => '08:00',
                    'end_time'        => '22:00',
                    'is_all_day'      => true,
                    'status'          => 'active',
                ]
            );
        }
    }
}
