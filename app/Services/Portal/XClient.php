<?php

namespace App\Services\Portal;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class XClient
{
    private Client $http;
    private ?string $bearer = null;

    public function __construct()
    {
        $this->bearer = config('services.x.bearer_token');
        $this->http = new Client([
            'base_uri' => 'https://api.x.com/',
            'timeout' => 20,
        ]);
    }

    /**
     * Search tweets via X API v2.
     *
     * @return array decoded JSON response
     */
    public function search(array $params): array
    {
        if (!$this->bearer) {
            throw new \RuntimeException('Missing X_BEARER_TOKEN');
        }

        $endpoint = config('services.x.search_endpoint', 'recent');
        $path = $endpoint === 'all'
            ? '2/tweets/search/all'
            : '2/tweets/search/recent';

        try {
            $res = $this->http->get($path, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearer,
                ],
                'query' => $params,
            ]);
        } catch (GuzzleException $e) {
            $body = null;

            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response) {
                    $body = (string) $response->getBody();
                }
            }

            $msg = 'X API request failed: ' . $e->getMessage();
            if ($body) {
                $msg .= ' | body=' . $body;
            }
            throw new \RuntimeException($msg, 0, $e);
        }
        return json_decode((string)$res->getBody(), true) ?? [];
    }
}