<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\DB;

class PortalEventService
{
    public function attachOrCreateEvent(array $data): int
    {
        // $data expects:
        // player_name
        // from_team
        // status
        // posted_at
        // raw_post_id
        //
        // optional:
        // needs_waiver (bool)
        // eligibility_note (string|null)

        $incomingNeedsWaiver = (bool)($data['needs_waiver'] ?? false);
        $incomingNote = $data['eligibility_note'] ?? null;
        $incomingNoElig = (bool)($data['no_eligibility_remaining'] ?? false);

        $eventQuery = DB::table('portal_events')
            ->where('player_name', $data['player_name'])
            ->where('status', $data['status']);

        if (is_null($data['from_team'])) {
            $eventQuery->whereNull('from_team');
        } else {
            $eventQuery->where('from_team', $data['from_team']);
        }

        $event = $eventQuery->first();

        if ($event) {
            // Update existing event
            DB::table('portal_events')
                ->where('id', $event->id)
                ->update([
                    'report_count' => $event->report_count + 1,
                    'first_reported_at' => min($event->first_reported_at, $data['posted_at']),
                    'needs_waiver' => (bool)($event->needs_waiver ?? false) || $incomingNeedsWaiver,
                    'no_eligibility_remaining' => (bool)($event->no_eligibility_remaining ?? false) || $incomingNoElig,
                    'eligibility_note' => ($event->eligibility_note ?? null) ?: $incomingNote,

                    'updated_at' => now(),
                ]);

            $eventId = $event->id;
        } else {
            // Create new event
            $eventId = DB::table('portal_events')->insertGetId([
                'player_name' => $data['player_name'],
                'from_team' => $data['from_team'],
                'status' => $data['status'],
                'first_reported_at' => $data['posted_at'],
                'report_count' => 1,
                'needs_waiver' => $incomingNeedsWaiver,
                'no_eligibility_remaining' => $incomingNoElig,
                'eligibility_note' => $incomingNote,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Attach raw post to event
        DB::table('portal_raw_posts')
            ->where('id', $data['raw_post_id'])
            ->update([
                'event_id' => $eventId,
                'updated_at' => now(),
            ]);

        return $eventId;
    }
}