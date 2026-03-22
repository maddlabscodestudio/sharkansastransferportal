<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalIngestFromFile extends Command
{
    protected $signature = 'portal:ingest-file {path} {source}';
    protected $description = 'Ingest portal posts from a JSON file';

    public function handle(): int
    {
        $path = $this->argument('path');
        $handle = $this->argument('source');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $source = DB::table('portal_sources')
            ->where('handle', $handle)
            ->first();

        if (!$source) {
            $this->error("Source not found: {$handle}");
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);

        $count = 0;

        foreach ($data as $row) {
            $externalId = (string)($row['id'] ?? Str::uuid());
            $text = $row['text'] ?? null;
            $postedAt = $row['posted_at'] ?? null;

            // Skip bad rows
            if (!$text || !$postedAt) {
                continue;
            }

            DB::table('portal_raw_posts')->updateOrInsert(
                ['external_post_id' => $externalId],
                [
                    'source_id' => $source->id,
                    'posted_at' => $postedAt,
                    'text' => $text,

                    // Optional fields (future Twitter use)
                    'permalink' => $row['permalink'] ?? null,
                    'payload' => isset($row['payload']) ? json_encode($row['payload']) : null,

                    // Reset processing state
                    'parse_status' => 'unparsed',
                    'parsed_at' => null,
                    'parse_error' => null,
                    'event_id' => null,
                    'from_team' => null,

                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $count++;
        }


        $this->info("Inserted {$count} raw posts");
        return self::SUCCESS;
    }
}