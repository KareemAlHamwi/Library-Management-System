<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\Cart\CartService;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $items = $this->cartService->getCartItems($user);
        $total = $this->cartService->getCartTotal($user);

        return response()->json([
            'items' => $items,
            'total' => $total,
            'count' => $items->count()
        ]);
    }

    public function add(AddToCartRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $book = Book::findOrFail($request->book_id);

            $cartItem = $this->cartService->addToCart(
                $user,
                $book,
                $request->quantity ?? 1
            );

            return response()->json([
                'message' => 'تم إضافة الكتاب للسلة بنجاح',
                'cart_item' => $cartItem
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(UpdateCartRequest $request, Book $book): JsonResponse
    {
        try {
            $user = Auth::user();

            $this->cartService->updateQuantity(
                $user,
                $book,
                $request->quantity
            );

            return response()->json([
                'message' => 'تم تحديث الكمية بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function remove(Book $book): JsonResponse
    {
        $user = Auth::user();

        $this->cartService->removeFromCart($user, $book);

        return response()->json([
            'message' => 'تم حذف الكتاب من السلة'
        ]);
    }

    public function clear(): JsonResponse
    {
        $user = Auth::user();

        $this->cartService->clearCart($user);

        return response()->json([
            'message' => 'تم تفريغ السلة بنجاح'
        ]);
    }
}
