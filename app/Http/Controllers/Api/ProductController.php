<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Product\StoreProductRequest;
use App\Http\Requests\Api\Product\UpdateProductRequest;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // عرض جميع المنتجات مع البحث والفلترة حسب القسم
    public function index(Request $request)
    {
        $query = Product::with('owner')->where('status', 'active');

        // الفلترة بالقسم (Category)
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // البحث بالاسم أو الوصف (Search)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // العرض بنظام الصفحات (12 عنصر بالصفحة)
        $products = $query->latest()->paginate(12);

        return ProductResource::collection($products);
    }

    public function show(string $id): ProductResource
    {
        $product = Product::with('owner')
            ->where('status', 'active')
            ->findOrFail($id);

        return new ProductResource($product);
    }

    // إضافة منتج جديد
    public function store(StoreProductRequest $request, SubscriptionService $subscriptions): JsonResponse
    {
        if (!$subscriptions->canCreateListing($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'لقد وصلت إلى الحد الشهري لإضافة المنتجات في خطتك الحالية',
            ], 422);
        }

        $imagePaths = [];

        // رفع صور المنتج وحفظ مساراتها
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = asset('storage/' . $path);
            }
        }

        $product = Product::create([
            'owner_id'        => $request->user()->id,
            'title'           => $request->title,
            'category'        => $request->category,
            'description'     => $request->description,
            'price_per_hour'  => $request->price_per_hour,
            'deposit_amount'  => $request->deposit_amount,
            'product_images'  => $imagePaths,
            'available_dates' => $request->available_dates,
            'start_time'      => $request->start_time ?? '00:00',
            'end_time'        => $request->end_time ?? '23:59',
            'is_all_day'      => $request->boolean('is_all_day', true),
            'status'          => 'active',
        ]);

        $subscriptions->recordListing($request->user());

        return response()->json([
            'message' => 'تم حفظ ونشر المنتج بنجاح',
            'product' => $product,
        ], 201);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        // التأكد من أن المستخدم الحالي هو صاحب المنتج
        if ($request->user()->id !== $product->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، لا تملك الصلاحية لتعديل هذا المنتج'
            ], 403);
        }

        $validated = $request->validated();

        // معالجة رفع الصور الجديدة إذا وجدت (حقل images موحّد مع الإنشاء)
        if ($request->hasFile('images')) {
            $uploadedImages = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $uploadedImages[] = asset('storage/' . $path);
            }
            $validated['product_images'] = $uploadedImages;
            unset($validated['images']);
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المنتج بنجاح',
            'data'    => new ProductResource($product->fresh())
        ]);
    }

    /**
     * تجميد أو تفعيل منتج (Freeze/Unfreeze)
     */
    public function toggleStatus(Request $request, Product $product)
    {
        // التأكد من أن المستخدم الحالي هو صاحب المنتج
        if ($request->user()->id !== $product->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، لا تملك الصلاحية لتعديل حالة هذا المنتج'
            ], 403);
        }

        // تبديل الحالة بين active و frozen
        $newStatus = $product->status === 'frozen' ? 'active' : 'frozen';
        $product->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => $newStatus === 'frozen' ? 'تم تجميد المنتج بنجاح' : 'تم تفعيل المنتج بنجاح',
            'data'    => new ProductResource($product->fresh())
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Request $request, Product $product)
    {
        // التأكد من أن المستخدم الحالي هو صاحب المنتج
        if ($request->user()->id !== $product->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، لا تملك الصلاحية لحذف هذا المنتج'
            ], 403);
        }

        $isCurrentlyRented = $product->rentalRequests()
            ->where('owner_status', 'accepted')
            ->where('end_time', '>=', now())
            ->exists();

        if ($isCurrentlyRented) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المنتج لأنه مؤجر حالياً أو لديه حجز مؤكد',
            ], 409);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنتج بنجاح'
        ]);
    }
}
