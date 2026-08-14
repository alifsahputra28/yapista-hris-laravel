<?php

namespace App\Services;

use App\Models\Event;

class EventMetricsService
{
    /**
     * @return array{
     *   total: int,
     *   draft: int,
     *   active: int,
     *   closed: int,
     *   cancelled: int,
     *   this_month: int,
     *   active_today: int
     * }
     */
    public function counts(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $today = now()->toDateString();

        $counts = Event::query()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft")
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) AS closed")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled")
            ->selectRaw('SUM(CASE WHEN event_date BETWEEN ? AND ? THEN 1 ELSE 0 END) AS this_month', [$monthStart, $monthEnd])
            ->selectRaw("SUM(CASE WHEN status = 'active' AND event_date = ? THEN 1 ELSE 0 END) AS active_today", [$today])
            ->first();

        return [
            'total' => (int) $counts?->total,
            'draft' => (int) $counts?->draft,
            'active' => (int) $counts?->active,
            'closed' => (int) $counts?->closed,
            'cancelled' => (int) $counts?->cancelled,
            'this_month' => (int) $counts?->this_month,
            'active_today' => (int) $counts?->active_today,
        ];
    }
}
