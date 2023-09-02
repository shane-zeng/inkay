<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Spatie\GoogleCalendar\Event;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GoogleCalendarEventTimeCommand extends Command
{
    protected $signature = 'google:calendar-event-time {--startTime=} {--endTime=}';

    protected $description = 'Command description';

    public function handle(): int
    {
        $monthStart = $this->option('startTime')
            ? Carbon::parse($this->option('startTime'))->startOfDay()
            : Carbon::now()->subMonth()->startOfMonth();
        $monthEnd = $this->option('endTime')
            ? Carbon::parse($this->option('endTime'))->endOfDay()
            : Carbon::now()->subMonth()->endOfMonth();

        $events = Event::get($monthStart, $monthEnd);

        $eventGroups = [];

        foreach ($events as $event) {
            $summary = trim($event->summary);

            if (in_array($summary, config('google-calendar.except_events'))) {
                continue;
            }

            if (! isset($eventGroups[$summary])) {
                $eventGroups[$summary] = 0;
            }

            $hour = $event->endDateTime->floatDiffInHours($event->startDateTime);

            $eventGroups[$summary] += $hour;
        }

        $messages = '';

        foreach ($eventGroups as $eventName => $hour) {
            if (empty($messages)) {
                $messages .= "> {$monthStart} - {$monthEnd}\n";
            }

            $messages .= "**{$eventName}：**  {$hour} 小時\n";
        }

        if (! empty($messages)) {
            Http::post(config('google-calendar.discord_webhook_url'), [
                'content' => $messages,
            ]);
        }

        return CommandAlias::SUCCESS;
    }
}
