<?php

namespace App\Repositories\Contracts\Event;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventParticipation;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface EventRepositoryInterface
{
    public function getOpenEvents(int $perPage): LengthAwarePaginator;

    public function getAllEvents(array $filters, int $perPage): LengthAwarePaginator;

    public function getUserEvents(User $user, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?Event;

    public function create(array $data): Event;

    public function update(Event $event, array $data): Event;

    public function delete(Event $event): void;

    public function changeStatus(Event $event, EventStatus $status): Event;

    public function isParticipant(User $user, Event $event): bool;

    public function join(User $user, Event $event): EventParticipation;

    public function leave(User $user, Event $event): void;

    public function getSupervisedEvents(User $supervisor, int $perPage): LengthAwarePaginator;
}
