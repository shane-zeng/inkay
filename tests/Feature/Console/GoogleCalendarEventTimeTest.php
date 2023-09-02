<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\GoogleCalendar\Event;

test('test command google:calendar-event-time is ok', function () {
    // arrange
    Mockery::mock('overload:'.Event::class)
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            (object) [
                'summary' => 'test1',
                'startDateTime' => Carbon::parse('2023-03-01 09:00:00'),
                'endDateTime' => Carbon::parse('2023-03-01 10:00:00'),
            ],
        ]));
    Http::fake();
    // act
    $this->artisan('google:calendar-event-time', [
        '--startTime' => '2023-03-01',
        '--endTime' => '2023-03-31',
    ])
        // assert
        ->assertSuccessful();
    Http::assertSent(
        fn ($request) => $request->url() === config('google-calendar.discord_webhook_url')
            && Str::containsAll($request['content'], [
                '2023-03-01 00:00:00 - 2023-03-31 23:59:59',
                'test1',
                '1 小時',
            ])
    );
});

test('test command google:calendar-event-time is ok when events is empty', function () {
    // arrange
    Mockery::mock('overload:'.Event::class)
        ->shouldReceive('get')
        ->once()
        ->andReturn(collect());
    Http::fake();
    // act
    $this->artisan('google:calendar-event-time', [
        '--startTime' => '2023-03-01',
        '--endTime' => '2023-03-31',
    ])
        // assert
        ->assertSuccessful();
    Http::assertNotSent(fn ($request) => $request->url() === config('google-calendar.discord_webhook_url'));
});
