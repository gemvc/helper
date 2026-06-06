<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use Gemvc\Helper\ChatGptClient;
use Gemvc\Http\ApiCall;
use Gemvc\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

class FakeApiCall extends ApiCall
{
    public function __construct(private string|false $response = false)
    {
    }

    public function post(string $url, array $data): string|false
    {
        return $this->response;
    }
}

class ChatGptClientTest extends TestCase
{
    private function createClientWithApiCall(string|false $response, ?string $apiKey = 'test-key'): ChatGptClient
    {
        $client = new ChatGptClient($apiKey);
        $property = new \ReflectionProperty(ChatGptClient::class, 'apiCall');
        $property->setValue($client, new FakeApiCall($response));

        return $client;
    }

    public function testConstructorSetsAuthorizationHeader(): void
    {
        $client = new ChatGptClient('my-api-key');
        $property = new \ReflectionProperty(ChatGptClient::class, 'apiCall');
        $apiCall = $property->getValue($client);

        $this->assertSame('Bearer my-api-key', $apiCall->authorizationHeader);
    }

    public function testConstructorWithNullApiKeyUsesEmptyKey(): void
    {
        $client = new ChatGptClient(null);
        $property = new \ReflectionProperty(ChatGptClient::class, 'apiCall');
        $apiCall = $property->getValue($client);

        $this->assertSame('Bearer ', $apiCall->authorizationHeader);
    }

    public function testSendRequestReturnsSuccessOnValidResponse(): void
    {
        $client = $this->createClientWithApiCall('{"id":"cmpl-123","choices":[]}');
        $response = $client->sendRequest('chat/completions', ' system prompt ', ' user question ');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->response_code);
        $this->assertIsObject($response->data);
    }

    public function testSendRequestReturnsBadRequestWhenPostFails(): void
    {
        $client = $this->createClientWithApiCall(false);
        $response = $client->sendRequest('chat/completions', 'sys', 'user');

        $this->assertSame(400, $response->response_code);
        $this->assertSame('chat gpt did not answer', $response->service_message);
    }
}
