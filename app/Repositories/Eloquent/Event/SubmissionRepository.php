<?php

namespace App\Repositories\Eloquent\Event;

use App\Enums\SubmissionStatus;
use App\Models\Event;
use App\Models\EventPointLog;
use App\Models\EventSubmission;
use App\Models\User;
use App\Repositories\Contracts\Event\SubmissionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class SubmissionRepository implements SubmissionRepositoryInterface
{
    public function getEventSubmissions(Event $event, array $filters, int $perPage): LengthAwarePaginator
    {
        return EventSubmission::query()
            ->where('event_id', $event->id)
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->with('user')
            ->latest()
            ->paginate($perPage);
    }

    public function getUserSubmissions(User $user, int $perPage): LengthAwarePaginator
    {
        return EventSubmission::query()
            ->where('user_id', $user->id)
            ->with('event')
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?EventSubmission
    {
        return EventSubmission::with(['event', 'user', 'eventPointLogs'])->find($id);
    }

    public function hasSubmitted(User $user, Event $event): bool
    {
        return EventSubmission::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();
    }

    public function create(array $data): EventSubmission
    {
        return EventSubmission::create($data);
    }

    public function approve(EventSubmission $submission): EventSubmission
    {
        $submission->update([
            'status' => SubmissionStatus::APPROVED,
            'reviewed_at' => now(),
        ]);

        return $submission->fresh();
    }

    public function reject(EventSubmission $submission): EventSubmission
    {
        $submission->update([
            'status' => SubmissionStatus::REJECTED,
            'reviewed_at' => now(),
        ]);

        return $submission->fresh();
    }

    public function awardPoints(array $data): EventPointLog
    {
        return EventPointLog::create($data);
    }
}
