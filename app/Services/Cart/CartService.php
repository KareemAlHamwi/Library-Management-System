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

        if ($book->available_stock_copies < $quantity) {
            throw new \Exception('The book is not available in this quantity.');
        }


        $cart = Cart::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if ($cart) {

            $cart->increment('quantity', $quantity);


            $cart->refresh();

            return $cart;
        }


        $cart = Cart::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'quantity' => $quantity
        ]);

        return $cart;
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
            throw new \Exception('The requested quantity is not available.');
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

        return $cart ? (int) $cart->quantity : 0;
    }
}
