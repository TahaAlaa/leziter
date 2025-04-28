<?php

namespace App\Leziter\Facades;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class RestApiClient
{

    private string $baseUrl;
    private string $token;
    /**
     * @var true
     */
    private bool $collectResponses = false;
    private array $collectedResponses = [];

    public function __construct(string $apiUrl, string $token)
    {
        $this->baseUrl = $apiUrl;
        $this->token = $token;
    }

    public function collectResponses(): void
    {
        $this->collectResponses = true;
    }

    public function resetCollectedResponses(): void
    {
        $this->collectedResponses = [];
    }

    /**
     * @throws GuzzleException
     */
    private function sendHttpRequest(string $uri, string $method, array $data = []): array
    {
        $client = new Client();
        $url = rtrim($this->baseUrl, '/') . $uri;
        $serverResponse = $client->request($method, $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ],
            'json' => $data,
            'http_errors' => false
        ]);

        $response = [
            'status' => $serverResponse->getStatusCode(),
            'data' => json_decode($serverResponse->getBody()->getContents(), true)
        ];

        $this->collectResponse($url, $response);

        return $response;
    }

    private function collectResponse(string $url, array $response): void
    {
        if (!$this->collectResponses) return;
        $this->collectedResponses[] = ['url' => $url, 'response' => $response];
    }

    /**
     * @throws GuzzleException
     */
    public function get(string $uri): array
    {
        return $this->sendHttpRequest($uri, 'GET');
    }

    /**
     * @throws GuzzleException
     */
    public function post(string $uri, array $data = []): array
    {
        return $this->sendHttpRequest($uri, 'POST', $data);
    }

    /**
     * @throws GuzzleException
     */
    public function postFile(string $uri, string $filePath, array $data = []): array
    {
        $formData = $this->convertToMultipartFormData($data);

        $client = new Client();
        $url = rtrim($this->baseUrl, '/') . $uri;
        $response = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => basename($filePath)
                ],
                ...$formData
            ],
            'http_errors' => false
        ]);

        $response = [
            'status' => $response->getStatusCode(),
            'data' => json_decode($response->getBody()->getContents(), true)
        ];

        $this->collectResponse($url, $response);

        return $response;
    }

    /**
     * @throws GuzzleException
     */
    public function put(string $uri, $data = []): array
    {
        return $this->sendHttpRequest($uri, 'PUT', $data);
    }

    /**
     * @throws GuzzleException
     */
    public function delete(string $uri): array
    {
        return $this->sendHttpRequest($uri, 'DELETE');
    }

    /**
     * @param array $data
     * @return array
     */
    private function convertToMultipartFormData(array $data): array
    {
        $formData = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $formData[] = ['name' => $key, 'contents' => $value];
            }
        }
        return $formData;
    }

    public function getCollectedResponses(): array
    {
        return $this->collectedResponses;
    }
}
