<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SavedItem\StoreSavedItemRequest;
use App\Http\Resources\Api\SavedItemResource;
use App\Models\Product;
use App\Models\SavedItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedItemController extends Controller
{
    // عرض جميع العناصر المحفوظة للمستخدم الحالي (المفضلة)
    public function index(Request $request)
    {
        $savedItems = SavedItem::with('product.owner')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return SavedItemResource::collection($savedItems);
    }

    // إضافة منتج إلى المحفوظات
    public function store(StoreSavedItemRequest $request): JsonResponse
    {
        $savedItem = SavedItem::firstOrCreate([
            'user_id'    => $request->user()->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ المنتج بنجاح',
            'data'    => new SavedItemResource($savedItem->load('product.owner')),
        ], 201);
    }

    // إزالة منتج من المحفوظات عبر رقم المنتج
    public function destroy(Request $request, string $product): JsonResponse
    {
        $deleted = SavedItem::where('user_id', $request->user()->id)
            ->where('product_id', $product)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المنتج غير موجود ضمن المحفوظات',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إزالة المنتج من المحفوظات',
        ]);
    }

    // تبديل حالة الحفظ لمنتج معيّن (حفظ / إلغاء حفظ) بضغطة واحدة
    public function toggle(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        $savedItem = SavedItem::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($savedItem) {
            $savedItem->delete();

            return response()->json([
                'success'  => true,
                'is_saved' => false,
                'message'  => 'تم إزالة المنتج من المحفوظات',
            ]);
        }

        SavedItem::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);

        return response()->json([
            'success'  => true,
            'is_saved' => true,
            'message'  => 'تم حفظ المنتج بنجاح',
        ]);
    }
}
