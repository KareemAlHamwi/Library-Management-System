<?php

namespace App\Repositories\Eloquent\Event;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventParticipation;
use App\Models\User;
use App\Repositories\Contracts\Event\EventRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EventRepository implements EventRepositoryInterface
{
    public function getOpenEvents(int $perPage): LengthAwarePaginator
    {
        return Event::query()
            ->where('status', EventStatus::OPEN)
            ->with(['book', 'supervisor'])
            ->latest()
            ->paginate($perPage);
    }

    public function getAllEvents(array $filters, int $perPage): LengthAwarePaginator
    {
        return Event::query()
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['book_id'] ?? null, fn ($q, $v) => $q->where('book_id', $v))
            ->with(['book', 'supervisor'])
            ->latest()
            ->paginate($perPage);
    }

    public function getUserEvents(User $user, int $perPage): LengthAwarePaginator
    {
        return Event::query()
            ->whereHas('participations', fn ($q) => $q->where('user_id', $user->id))
            ->with(['book'])
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Event
    {
        return Event::with(['book', 'supervisor'])->find($id);
    }

    public function create(array $data): Event
    {
        return Event::create($data);
    }

    public function update(Event $event, array $data): Event
    {
        $event->update($data);

        return $event->fresh();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }

    public function changeStatus(Event $event, EventStatus $status): Event
    {
        $event->update(['status' => $status]);

        return $event->fresh();
    }

    public function isParticipant(User $user, Event $event): bool
    {
        return EventParticipation::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();
    }

    public function join(User $user, Event $event): EventParticipation
    {
        return EventParticipation::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);
    }

    public function leave(User $user, Event $event): void
    {
        EventParticipation::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->delete();
    }

    public function getSupervisedEvents(User $supervisor, int $perPage): LengthAwarePaginator
    {
        return Event::query()
            ->where('supervisor_id', $supervisor->id)
            ->with(['book'])
            ->latest()
            ->paginate($perPage);
    }
}
