<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Services\EventMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EventMetricsBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public static function monthEnds(): array
    {
        return [['2026-08-31'], ['2028-02-29'], ['2026-12-31']];
    }

    #[DataProvider('monthEnds')]
    public function test_month_and_today_include_date_and_cast_datetime_values(string $today): void
    {
        $this->travelTo(Carbon::parse($today.' 15:30:00', 'Asia/Jakarta'));
        try {
            $dates = [
                now()->startOfMonth()->subDay()->toDateString(),
                now()->startOfMonth()->toDateString(),
                $today,
                $today,
                now()->addDay()->toDateString(),
            ];
            foreach ($dates as $index => $date) {
                $event = Event::create([
                    'name' => 'Synthetic boundary '.$index,
                    'event_date' => $date,
                    'target_type' => 'all',
                    'status' => $index === 1 ? 'closed' : 'active',
                ]);
                // MySQL DATE values and SQLite's Eloquent date casts differ in storage.
                if (in_array($index, [1, 3], true)) {
                    DB::table('events')->where('id', $event->id)->update(['event_date' => $date]);
                }
            }

            $counts = app(EventMetricsService::class)->counts();
            $this->assertSame(5, $counts['total']);
            $this->assertSame(3, $counts['this_month']);
            $this->assertSame(2, $counts['active_today']);
            $this->assertSame(4, $counts['active']);
            $this->assertSame(1, $counts['closed']);
        } finally {
            $this->travelBack();
        }
    }
}
