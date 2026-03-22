<?php

namespace App\Services\SportsDataIO;

use Illuminate\Support\Facades\Http;

class SportsDataIoClient
{
    public function get(string $path, array $query = []): array
    {
        $baseUrl = rtrim(config('services.sportsdataio.base_url'), '/');
        $key = config('services.sportsdataio.key');

        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => $key,
            ])
            ->get($baseUrl . $path, $query)
            ->throw();

        return $response->json();
    }
}