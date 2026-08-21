<?php

namespace App\Http\Controllers\Borrow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Borrow\RequestBorrowRequest;
use App\Http\Resources\Borrow\BorrowResource;
use App\Http\Resources\Borrow\FineResource;
use App\Models\Book;
use App\Models\Borrow;
use App\Models\Fine;
use App\Services\Borrow\BorrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BorrowController extends Controller
{
    public function __construct(
        private readonly BorrowService $borrowService
    ) {}

    // ── User ────────────────────────────────────────────────

    public function store(RequestBorrowRequest $request, Book $book): JsonResponse
    {
        try {
            $borrow = $this->borrowService->request(
                $request->user(),
                $book,
                $request->validated('due_date')
            );

            return response()->json(new BorrowResource($borrow->load(['book'])), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function myBorrows(Request $request): AnonymousResourceCollection
    {
        return BorrowResource::collection(
            $this->borrowService->getUserBorrows(
                $request->user(),
                $request->only(['status']),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function show(int $id): JsonResponse
    {
        $borrow = $this->borrowService->getById($id);

        if (! $borrow) {
            return response()->json(['message' => 'Borrow not found.'], 404);
        }

        return response()->json(new BorrowResource($borrow));
    }

    public function myFines(Request $request): AnonymousResourceCollection
    {
        return FineResource::collection(
            $this->borrowService->getUserFines(
                $request->user(),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function payFine(Request $request, Fine $fine): JsonResponse
    {
        try {
            $paid = $this->borrowService->payFine($request->user(), $fine);

            return response()->json(new FineResource($paid));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    // ── Admin ────────────────────────────────────────────────

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        return BorrowResource::collection(
            $this->borrowService->getAllBorrows(
                $request->only(['status', 'user_id', 'book_id']),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function adminShow(int $id): JsonResponse
    {
        $borrow = $this->borrowService->getById($id);

        if (! $borrow) {
            return response()->json(['message' => 'Borrow not found.'], 404);
        }

        return response()->json(new BorrowResource($borrow));
    }

    public function approve(Borrow $borrow): JsonResponse
    {
        try {
            return response()->json(new BorrowResource($this->borrowService->approve($borrow)));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function reject(Borrow $borrow): JsonResponse
    {
        try {
            return response()->json(new BorrowResource($this->borrowService->reject($borrow)));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function markReturned(Borrow $borrow): JsonResponse
    {
        try {
            return response()->json(new BorrowResource($this->borrowService->markReturned($borrow)));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function markOverdue(Borrow $borrow): JsonResponse
    {
        try {
            return response()->json(new BorrowResource($this->borrowService->markOverdue($borrow)));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
