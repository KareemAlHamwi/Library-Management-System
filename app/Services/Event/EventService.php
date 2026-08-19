<?php

namespace App\Services\Event;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventParticipation;
use App\Models\User;
use App\Repositories\Contracts\Event\EventRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {}

    public function getOpenEvents(int $perPage): LengthAwarePaginator
    {
        return $this->eventRepository->getOpenEvents($perPage);
    }

    public function getAllEvents(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->eventRepository->getAllEvents($filters, $perPage);
    }

    public function getUserEvents(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->eventRepository->getUserEvents($user, $perPage);
    }

    public function getById(int $id): ?Event
    {
        return $this->eventRepository->findById($id);
    }

    public function create(User $admin, array $data): Event
    {
        return $this->eventRepository->create([
            ...$data,
            'supervisor_id' => $admin->id,
        ]);
    }

    public function update(Event $event, array $data): Event
    {
        return $this->eventRepository->update($event, $data);
    }

    public function delete(Event $event): void
    {
        $this->eventRepository->delete($event);
    }

    public function changeStatus(Event $event, string $status): Event
    {
        $new = EventStatus::from($status);

        $allowed = [
            EventStatus::DRAFT->value => EventStatus::OPEN,
            EventStatus::OPEN->value => EventStatus::CLOSED,
            EventStatus::CLOSED->value => EventStatus::ARCHIVED,
        ];

        if (! isset($allowed[$event->status->value])) {
            throw new \Exception('This event cannot be transitioned further.', 422);
        }

        if ($allowed[$event->status->value] !== $new) {
            throw new \Exception(
                "Invalid transition. Expected: {$allowed[$event->status->value]->value}.",
                422
            );
        }

        return $this->eventRepository->changeStatus($event, $new);
    }

    public function join(User $user, Event $event): EventParticipation
    {
        if ($event->status !== EventStatus::OPEN) {
            throw new \Exception('This event is not open for participation.', 422);
        }

        if ($this->eventRepository->isParticipant($user, $event)) {
            throw new \Exception('You have already joined this event.', 409);
        }

        return $this->eventRepository->join($user, $event);
    }

    public function leave(User $user, Event $event): void
    {
        if (! $this->eventRepository->isParticipant($user, $event)) {
            throw new \Exception('You are not a participant in this event.', 409);
        }

        $this->eventRepository->leave($user, $event);
    }

    public function isParticipant(User $user, Event $event): bool
    {
        return $this->eventRepository->isParticipant($user, $event);
    }

    public function getSupervisedEvents(User $supervisor, int $perPage): LengthAwarePaginator
    {
        return $this->eventRepository->getSupervisedEvents($supervisor, $perPage);
    }

    public function updateAsSupervisor(User $supervisor, Event $event, array $data): Event
    {
        $this->assertIsSupervisorOf($supervisor, $event);

        return $this->eventRepository->update($event, $data);
    }

    public function changeStatusAsSupervisor(User $supervisor, Event $event, string $status): Event
    {
        $this->assertIsSupervisorOf($supervisor, $event);

        return $this->changeStatus($event, $status);
    }

    private function assertIsSupervisorOf(User $user, Event $event): void
    {
        if ($event->supervisor_id !== $user->id) {
            throw new \Exception('You are not the supervisor of this event.', 403);
        }
    }

    public function createAsSupervisor(User $supervisor, array $data): Event
    {
        return $this->eventRepository->create([
            ...$data,
            'supervisor_id' => $supervisor->id,
        ]);
    }

    public function deleteAsSupervisor(User $supervisor, Event $event): void
    {
        $this->assertIsSupervisorOf($supervisor, $event);
        $this->eventRepository->delete($event);
    }
}
