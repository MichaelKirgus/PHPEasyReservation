<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function __construct(private readonly EventService $events)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(Event::query()->orderBy('start_at', 'desc')->get());
    }

    public function store(EventRequest $request): JsonResponse
    {
        $event = Event::create($request->validated());
        return response()->json($event, 201);
    }

    public function update(EventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());
        return response()->json($event);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function upcoming(): JsonResponse
    {
        $list = $this->events->upcoming(10)->map(fn ($e) => [
            'id' => $e->id,
            'title' => $e->title,
            'start_at' => $e->start_at,
            'end_at' => $e->end_at,
            'location' => $e->location,
            'active' => $e->active,
            'display' => $this->events->format($e),
        ]);

        $next = $this->events->next();

        return response()->json([
            'next' => $next ? $this->events->format($next) : null,
            'upcoming' => $list,
        ]);
    }
}
