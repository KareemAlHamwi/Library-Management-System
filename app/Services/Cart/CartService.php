<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\User;
use App\Models\Book;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function addToCart(User $user, Book $book, int $quantity = 1)
    {
        // التحقق من توفر الكتاب
        if ($book->available_stock_copies < $quantity) {
            throw new \Exception('الكتاب غير متوفر بهذه الكمية');
        }

        // إضافة أو تحديث الكمية
        return Cart::updateOrCreate(
            [
                'user_id' => $user->id,
                'book_id' => $book->id
            ],
            [
                'quantity' => DB::raw("quantity + $quantity")
            ]
        );
    }

    public function removeFromCart(User $user, Book $book)
    {
        return Cart::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->delete();
    }

    public function updateQuantity(User $user, Book $book, int $quantity)
    {
        if ($quantity <= 0) {
            return $this->removeFromCart($user, $book);
        }

        if ($book->available_stock_copies < $quantity) {
            throw new \Exception('الكمية المطلوبة غير متوفرة');
        }

        return Cart::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->update(['quantity' => $quantity]);
    }

    public function clearCart(User $user)
    {
        return Cart::where('user_id', $user->id)->delete();
    }

    public function getCartTotal(User $user): int
    {
        return Cart::where('user_id', $user->id)
            ->with('book')
            ->get()
            ->sum(fn($item) => $item->quantity * $item->book->price);
    }

    public function getCartItems(User $user)
    {
        return Cart::where('user_id', $user->id)
            ->with('book')
            ->get();
    }

    public function isInCart(User $user, Book $book): bool
    {
        return Cart::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();
    }

    public function getItemQuantity(User $user, Book $book): int
    {
        $cart = Cart::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        return $cart ? $cart->quantity : 0;
    }
}
