<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function all(): array
    {
        return Cache::remember('settings.all', now()->addMinutes(5), function () {
            return Setting::query()->pluck('value', 'name')->toArray();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    public function refresh(): void
    {
        Cache::forget('settings.all');
        $this->all();
    }

    public function isTokenRequired(): bool
    {
        return (int) $this->get('reservation_token_enabled', 0) === 1;
    }

    public function siteToken(): ?string
    {
        return $this->get('reservation_token');
    }

    public function upcomingEvents(): array
    {
        $raw = (string) ($this->get('reservation_upcoming_events', '') ?? '');
        $lines = preg_split('/\r?\n/', $raw) ?: [];
        return array_values(array_filter(array_map('trim', $lines), fn ($v) => $v !== ''));
    }

    public function nextEvent(): ?string
    {
        $list = $this->upcomingEvents();
        if (count($list) === 0) {
            return null;
        }

        $now = time();
        foreach ($list as $item) {
            $ts = strtotime($item);
            if ($ts !== false && $ts >= $now) {
                return $item;
            }
        }

        return $list[0] ?? null;
    }

    public function upcomingEventsListFormatted(string $bullet = '• '): string
    {
        $list = $this->upcomingEvents();
        if (count($list) === 0) {
            return '';
        }

        return implode("\n", array_map(fn ($v) => $bullet.$v, $list));
    }
}
