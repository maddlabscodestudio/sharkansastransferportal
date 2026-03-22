<?php

namespace App\Services\Portal;

class PortalPostParser
{
    // football terms to exclude for multi-sport sources (not exhaustive; good enough for v1)
    private array $footballTerms = [
        'qb','quarterback','wr','wide receiver','rb','running back','te','tight end',
        'ol','offensive line','dt','defensive tackle','dl','defensive line','linebacker','lb','corner','cb','safety','fs','ss',
        'tackles','sacks','yards','touchdowns','tds','spring practice','depth chart','snap count'
    ];

    private array $excludePhrases = [
        'visit', 'visiting', 'offer', 'offered', 'contacted', 'finalist', 'top', 'crystal ball',
        'committed', 'commitment', 'signs', 'signed'
    ];

    /**
     * Normalize tweet text for parsing:
     * - decode HTML entities (&amp; etc.)
     * - collapse whitespace
     * - remove leading "RT @user:" prefix
     * - remove leading "NEWS:" / "SOURCE:" / "UPDATE:" prefixes (sometimes stacked)
     */
    private function normalize(string $text): string
    {
        $t = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        // normalize curly quotes/apostrophes
        $t = str_replace(["’", "‘", "“", "”"], ["'", "'", '"', '"'], $t);

        // collapse whitespace
        $t = trim(preg_replace('/\s+/', ' ', $t));

        // Strip RT header: "RT @user:"
        $t = preg_replace('/^RT\s+@\w+\s*:\s*/i', '', $t);

        // Strip stacked prefixes like "NEWS:" / "SOURCE:" / "UPDATE:"
        for ($i = 0; $i < 3; $i++) {
            $t = preg_replace('/^(?:NEWS|SOURCE|UPDATE)\s*:\s*/i', '', $t);
        }

        // Remove trailing parenthetical handles or notes, e.g. "Sean Craig (@SStacks247)"
        $t = preg_replace('/\s*\(@\w+\)\s*/', ' ', $t);

        // Remove stray t.co links (often at end)
        $t = preg_replace('/https?:\/\/t\.co\/\w+/i', '', $t);

        return trim(preg_replace('/\s+/', ' ', $t));
    }

    public function parseEntered(string $text, bool $needsSportFilter = false): ?array
    {
        $text = $this->normalize($text);
        $t = strtolower($text);

        // Always skip obvious football content even if the source isn't marked needs_sport_filter
        if ($this->containsFootballTerms($text)) {
            return null;
        }

        if (!str_contains($t, 'portal')) {
            return null;
        }

        // Skip non-entry future tense (noise)
        if (preg_match('/\b(will|plans\s+to|expected\s+to|likely|set\s+to)\s+enter\s+the\s+(transfer\s+)?portal\b/iu', $text)) {
            return null;
        }
        if (preg_match('/\benter\s+the\s+(transfer\s+)?portal\s+after\s+the\s+season\b/iu', $text)) {
            return null;
        }

        // Skip multi-player posts for v1 (hard to split cleanly)
        if (
            preg_match('/\b(both|all)\b/i', $text) &&
            (str_contains($t, ' and ') || str_contains($t, ','))
        ) {
            return null;
        }

        // Exclude obvious non-entry chatter
        foreach ($this->excludePhrases as $bad) {
            if (str_contains($t, $bad)) {
                return null;
            }
        }

        if ($needsSportFilter && $this->containsFootballTerms($text)) {
            return null;
        }

        // Confidence: HIGH (strong verbs)
        $highPatterns = [
            '/^(?<player>.+?)\s+(has\s+)?(officially\s+)?entered\s+the\s+portal\b/iu',
            '/^(?<player>.+?)\s+(has\s+)?(officially\s+)?entered\s+the\s+transfer\s+portal\b/iu',
            '/^(?<player>.+?)\s+(has\s+)?(officially\s+)?hit\s+the\s+portal\b/iu',
            '/^(?<player>.+?)\s+is\s+in\s+the\s+(transfer\s+)?portal\b/iu',
            '/^(?<player>.+?)\s+is\s+entering\s+the\s+(transfer\s+)?portal\b/iu',
        ];

        foreach ($highPatterns as $p) {
            if (preg_match($p, $text)) {
                [$team, $player] = $this->extractTeamAndPlayer($text);

                // If we still couldn't confidently split, fall back to null (skip)
                if (!$player) {
                    return null;
                }

                return [
                    'player_name' => $player,
                    'from_team'   => $team,
                    'status'      => 'entered',
                    'confidence'  => 'high',
                ];
            }
        }

        // Confidence: MED/LOW (looser language) — currently treated as NOT an entry for v1
        // We still return a low-confidence "entered" only when it actually says "enter the portal"
        // (You can change status to 'announced' later if you want a separate bucket.)
        $loosePatterns = [
            '/^(?<player>.+?)\s+(is\s+)?expected\s+to\s+enter\s+the\s+portal\b/i',
            '/^(?<player>.+?)\s+(will|plans\s+to|likely)\s+enter\s+the\s+portal\b/i',
            '/hearing\s+(?<player>.+?)\s+(will|is\s+going\s+to)\s+enter\s+the\s+portal\b/i',
        ];

        foreach ($loosePatterns as $p) {
            if (preg_match($p, $text, $m)) {
                // Per your decision: skip "will/expected to enter" to avoid noise.
                return null;
            }
        }

        return null;
    }

    private function cleanPlayer(string $player): string
    {
        $player = trim($player);

        // Normalize common suffix formatting: "Last, Jr" => "Last Jr"
        $player = preg_replace('/,\s*(Jr|Sr|II|III|IV)\b/i', ' $1', $player);

        // Remove stray commas (optional, but makes display cleaner)
        $player = str_replace(',', '', $player);

        $player = str_replace(["’", "‘"], ["'", "'"], $player);

        // strip "Team's " if it leaked into player
        $player = preg_replace('/^.+?\'s\s+/', '', $player);

        // remove position words if they sneak in
        $player = preg_replace('/\b(guard|wing|forward|center|G|F|C)\b/i', '', $player);

        $player = preg_replace('/\s{2,}/', ' ', $player);
        return trim($player, " \t\n\r\0\x0B-–—:|,.");
    }


    private function extractTeamAndPlayer(string $text): array
    {
        $text = $this->normalize($text);

        // Pattern 0: "Team / Player has entered ..."
        if (preg_match('/^(?<team>.+?)\s*\/\s*(?<player>[\p{L}\p{M}\.\-\' ]+?)\s*[,–—-]?\s+(has\s+)?(officially\s+)?(entered|hit|is)\b/iu', $text, $m)) {
            return [$this->cleanTeam($m['team'] ?? null), $this->cleanPlayer($m['player'] ?? '')];
        }

        // Pattern A: "Team's Player, has entered ..."
        if (preg_match('/^(?<team>.+?)[' . "\u{2019}" . '\']s\s+(?<player>[\p{L}\p{M}\.\-\' ]+?)\s*[,–—-]?\s+(?:has\s+)?(?:officially\s+)?(?:(?:entered)|(?:hit)|(?:is\s+entering)|(?:is))\b/iu',$text,$m)) {
            return [$this->cleanTeam($m["team"] ?? null), $this->cleanPlayer($m["player"] ?? "")];
        }

        // Pattern B: "{Team} G|F|C|guard... Player, has entered ..."
        if (preg_match('/^(?<team>.+?)\s+(?:G|F|C|guard|wing|forward|center|big man)\s+(?<player>[\p{L}\p{M}\.\-\' ]+?)\s*[,–—-]?\s+(?:has\s+)?(?:officially\s+)?(?:(?:entered)|(?:hit)|(?:is\s+entering)|(?:is))\b/iu',$text,$m)) {
            return [$this->cleanTeam($m["team"] ?? null), $this->cleanPlayer($m["player"] ?? "")];
        }

        // Pattern C: "{Team} junior/senior/grad transfer Player, has entered ..."
        if (preg_match('/^(?<team>.+?)\s+(?:freshman|sophomore|junior|senior|grad(?:\s+transfer)?|graduate(?:\s+transfer)?)\s+(?<player>[\p{L}\p{M}\.\-\' ]+?)\s*[,–—-]?\s+(?:has\s+)?(?:officially\s+)?(?:(?:entered)|(?:hit)|(?:is\s+entering)|(?:is))\b/iu',$text,$m)) {
            return [$this->cleanTeam($m["team"] ?? null), $this->cleanPlayer($m["player"] ?? "")];
        }

        // Fallback: "{Team} {Player} has entered the transfer portal"
        if (preg_match('/^(?<lead>.+?)\s+(has\s+)?(officially\s+)?entered\s+the\s+transfer\s+portal\b/iu', $text, $m)) {
            $lead = trim($m['lead'] ?? '');

            // If it contains "/", split explicitly
            if (str_contains($lead, '/')) {
                [$team, $player] = array_map('trim', explode('/', $lead, 2));
                return [$this->cleanTeam($team), $this->cleanPlayer($player)];
            }

            // Heuristic split: assume last 2–3 tokens are player (plus optional suffix)
            $parts = preg_split('/\s+/', $lead);
            if (count($parts) >= 3) {
                $suffixes = ['Jr','Sr','II','III','IV'];
                $last = preg_replace('/[^A-Za-z]/', '', end($parts));

                $playerTokens = 2;
                if (in_array($last, $suffixes, true)) {
                    $playerTokens = 3; // include suffix as part of player
                }

                $teamParts = array_slice($parts, 0, -$playerTokens);
                $playerParts = array_slice($parts, -$playerTokens);

                $team = implode(' ', $teamParts);
                $player = implode(' ', $playerParts);

                return [$this->cleanTeam($team), $this->cleanPlayer($player)];
            }
        }

        return [null, null];
    }



    /**
     * Best-effort extraction of team name if we couldn't confidently split team/player.
     * Used as a fallback. Safe to return null.
     */
    private function extractFromTeam(string $text): ?string
    {
        $text = $this->normalize($text);

        // Possessive: "Penn's Nick Spinoso ..."
        if (preg_match('/^(?<team>.+?)\'s\s+(?<player>[\p{L}\p{M}\.\-\' ]+?)\s+(has\s+)?(officially\s+)?(entered|hit|is)\b/iu', $text, $m)) {
            return $this->cleanTeam($m['team'] ?? null);
        }

        // "Team guard Player ..."
        if (preg_match('/^(?<team>.+?)\s+(guard|wing|forward|center)\s+(?<player>[\p{L}\p{M}\.\-\' ]+?)\s+(has\s+)?(officially\s+)?(entered|hit|is)\b/iu', $text, $m)) {
            return $this->cleanTeam($m['team'] ?? null);
        }

        // "Team G Player ..."
        if (preg_match('/^(?<team>.+?)\s+(G|F|C)\s+(?<player>[\p{L}\p{M}\.\-\' ]+?)\s+(has\s+)?(officially\s+)?(entered|hit|is)\b/iu', $text, $m)) {
            return $this->cleanTeam($m['team'] ?? null);
        }

        return null;
    }

    private function cleanTeam(?string $team): ?string {
        if (!$team) {
            return null;
        }

        $team = trim($team);
        $team = html_entity_decode($team, ENT_QUOTES | ENT_HTML5);

        // normalize curly quotes/apostrophes
        $team = str_replace(["’", "‘", "“", "”"], ["'", "'", '"', '"'], $team);

        // trim punctuation/whitespace
        $team = trim($team, " \t\n\r\0\x0B-–—:|,.");
        $team = preg_replace('/\s{2,}/', ' ', $team);

        // remove leading hashtag: "#LSU" -> "LSU"
        $team = preg_replace('/^#/', '', $team);

        // remove trailing position tag: "Mississippi State F" / "Kansas G" / "IU Indy C"
        $team = preg_replace('/\s+\b(G|F|C)\b$/i', '', $team);

        // remove trailing class-year / status words that sometimes leak into team
        $team = preg_replace(
            '/\s+\b(freshman|sophomore|junior|senior|grad(\s+transfer)?|graduate(\s+transfer)?)\b$/i',
            '',
            $team
        );

        $team = trim($team);

        // normalize common weird one-offs
        if (preg_match('/^st\.?\s+john$/i', $team)) {
            $team = "St. John's";
        }

        return $team !== '' ? $team : null;
    }

    private function containsFootballTerms(string $text): bool
    {
        // normalize to a "word-ish" string so \b works reliably
        $t = strtolower($this->normalize($text));
        $wordish = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $t);
        $wordish = trim(preg_replace('/\s+/', ' ', $wordish));

        foreach ($this->footballTerms as $term) {
            $term = strtolower($term);

            // If it's short (<=3 chars) like "te", "rb", "ol", only match as a whole word
            if (mb_strlen($term) <= 3) {
                if (preg_match('/\b' . preg_quote($term, '/') . '\b/u', $wordish)) {
                    return true;
                }
                continue;
            }

            // For longer terms ("quarterback", "wide receiver", "spring practice"), allow phrase match
            if (str_contains($wordish, $term)) {
                return true;
            }
        }

        return false;
    }

}