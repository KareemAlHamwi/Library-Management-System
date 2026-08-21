<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\ChangeStatusRequest;
use App\Http\Requests\Event\CreateEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\Event\EventResource;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventNotification;
use App\Services\Event\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
// use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification;

class EventController extends Controller
{
    public function __construct(
        private readonly EventService $eventService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return EventResource::collection(
            $this->eventService->getOpenEvents(
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function show(Event $event): EventResource
    {
        return new EventResource($event->load(['book', 'supervisor']));
    }

    public function myEvents(Request $request): AnonymousResourceCollection
    {
        return EventResource::collection(
            $this->eventService->getUserEvents(
                $request->user(),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function join(Request $request, Event $event): JsonResponse
    {
        try {
            $this->eventService->join($request->user(), $event);

            return response()->json(['message' => 'Successfully joined the event.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function leave(Request $request, Event $event): JsonResponse
    {
        try {
            $this->eventService->leave($request->user(), $event);

            return response()->json(['message' => 'Successfully left the event.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    // ── Admin ────────────────────────────────────────────────

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        return EventResource::collection(
            $this->eventService->getAllEvents(
                $request->only(['status', 'book_id']),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function store(CreateEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create($request->user(), $request->validated());

        $users = User::all();

        Notification::send($users, new EventNotification($event));

        return response()->json(new EventResource($event->load(['book', 'supervisor'])), 201);
    }

    public function update(UpdateEventRequest $request, Event $event): EventResource
    {
        return new EventResource(
            $this->eventService->update($event, $request->validated())->load(['book', 'supervisor'])
        );
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->eventService->delete($event);

        return response()->json(['message' => 'Event deleted successfully.']);
    }

    public function changeStatus(ChangeStatusRequest $request, Event $event): JsonResponse
    {
        try {
            $updated = $this->eventService->changeStatus($event, $request->validated('status'));

            return response()->json(new EventResource($updated));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    // ── Supervisor ───────────────────────────────────────────

    public function supervisorIndex(Request $request): AnonymousResourceCollection
    {
        return EventResource::collection(
            $this->eventService->getSupervisedEvents(
                $request->user(),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function supervisorUpdate(UpdateEventRequest $request, Event $event): JsonResponse
    {
        try {
            $updated = $this->eventService->updateAsSupervisor(
                $request->user(),
                $event,
                $request->validated()
            );

            return response()->json(new EventResource($updated->load(['book', 'supervisor'])));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function supervisorChangeStatus(ChangeStatusRequest $request, Event $event): JsonResponse
    {
        try {
            $updated = $this->eventService->changeStatusAsSupervisor(
                $request->user(),
                $event,
                $request->validated('status')
            );

            return response()->json(new EventResource($updated));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function supervisorStore(CreateEventRequest $request): JsonResponse
    {
        $event = $this->eventService->createAsSupervisor(
            $request->user(),
            $request->validated()
        );

        return response()->json(new EventResource($event->load(['book', 'supervisor'])), 201);
    }

    public function supervisorDestroy(Request $request, Event $event): JsonResponse
    {
        try {
            $this->eventService->deleteAsSupervisor($request->user(), $event);

            return response()->json(['message' => 'Event deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
