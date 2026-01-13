<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Str;

class IcsService
{
    public function __construct(
        private readonly EventService $events,
        private readonly SettingsService $settings,
    ) {
    }

    public function nextEventAttachment(): ?array
    {
        $event = $this->events->next();
        if (! $event || ! $event->start_at) {
            return null;
        }

        return [
            'name' => 'event.ics',
            'mime' => 'text/calendar; charset=utf-8',
            'data' => $this->buildIcs($event),
        ];
    }

    private function buildIcs(Event $event): string
    {
        $start = $event->start_at->copy()->utc();
        $end = $event->end_at
            ? $event->end_at->copy()->utc()
            : $event->start_at->copy()->utc()->addHour();

        $summary = $event->title ?: (string) $this->settings->get('reservation_name', 'Event');

        $locationParts = array_filter([
            $event->location ?: null,
            $event->city ?: null,
        ], fn ($v) => $v !== null && $v !== '');
        $location = empty($locationParts) ? '' : implode(' – ', $locationParts);

        $descriptionParts = array_filter([
            (string) ($event->notes ?? ''),
            (string) ($event->url ?? ''),
            (string) ($event->public_transport_url ?? ''),
        ], fn ($v) => $v !== '');
        $description = empty($descriptionParts) ? $summary : implode(' | ', $descriptionParts);

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $uid = ($event->id ? 'event-'.$event->id : (string) Str::uuid()).'@'.$host;

        $lines = array_filter([
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//PHPEasyReservation//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$this->escape($uid),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape($summary),
            $location !== '' ? 'LOCATION:'.$this->escape($location) : null,
            'DESCRIPTION:'.$this->escape($description),
            $event->url ? 'URL:'.$this->escape((string) $event->url) : null,
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        return implode("\r\n", $lines)."\r\n";
    }

    private function escape(string $value): string
    {
        return str_replace(
            ["\\", ";", ",", "\r\n", "\n", "\r"],
            ["\\\\", "\\;", "\\,", "\\n", "\\n", ''],
            $value
        );
    }
}
