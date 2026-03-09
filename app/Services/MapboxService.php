<?php

namespace App\Services;

use GuzzleHttp\Client;

class MapboxService
{
    protected $client;
    protected $accessToken;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://api.mapbox.com/']);
        $this->accessToken = config('services.mapbox.access_token');
    }

    public function getDistance(float $lat1, float $lng1, float $lat2, float $lng2)
    {
        try {
            $response = $this->client->get("directions/v5/mapbox/driving/{$lng1},{$lat1};{$lng2},{$lat2}", [
                'query' => [
                    'access_token' => $this->accessToken,
                    'geometries' => 'geojson',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['routes'][0]['distance'])) {
                return $data['routes'][0]['distance'] / 1000; // Return in KM
            }

            return 0;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mapbox Distance Error: " . $e->getMessage());
            return 0;
        }
    }

    public function geocode(string $address)
    {
        $response = $this->client->get("geocoding/v5/mapbox.places/" . urlencode($address) . ".json", [
            'query' => [
                'access_token' => $this->accessToken,
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
