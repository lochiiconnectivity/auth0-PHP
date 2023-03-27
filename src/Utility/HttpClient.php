<?php

declare(strict_types=1);

namespace Auth0\SDK\Utility;

use Auth0\SDK\Configuration\SdkConfiguration;
use Psr\Http\Message\ResponseInterface;
use function in_array;

/**
 * Class HttpClient.
 */
final class HttpClient
{
    /**
     * @var int
     */
    public const CONTEXT_AUTHENTICATION_CLIENT = 2;

    /**
     * @var int
     */
    public const CONTEXT_GENERIC_CLIENT        = 1;

    /**
     * @var int
     */
    public const CONTEXT_MANAGEMENT_CLIENT     = 3;

    /**
     * Instance of most recent HttpRequest.
     */
    private ?HttpRequest $lastRequest = null;

    /**
     * Mocked responses to pass to HttpRequest instances for testing.
     *
     * @var array<object>
     */
    private array $mockedResponses = [];

    /**
     * HttpClient constructor.
     *
     * @param SdkConfiguration  $configuration Required. Base configuration options for the SDK. See the SdkConfiguration class constructor for options.
     * @param int               $context       Required. The context the client is being created under, either CONTEXT_GENERIC_CLIENT, CONTEXT_AUTHENTICATION_CLIENT, or CONTEXT_MANAGEMENT_CLIENT.
     * @param string            $basePath      Optional. The base URI path from which additional pathing and parameters should be appended.
     * @param array<int|string> $headers       Optional. Additional headers to send with the HTTP request.
     */
    public function __construct(
        private SdkConfiguration $configuration,
        private int $context = self::CONTEXT_AUTHENTICATION_CLIENT,
        private string $basePath = '/',
        private array $headers = [],
    ) {
    }

    /**
     * Return a HttpRequest representation of the last built request.
     */
    public function getLastRequest(): ?HttpRequest
    {
        return $this->lastRequest;
    }

    /**
     * Create a new HttpRequest instance.
     *
     * @param string $method HTTP method to use (GET, POST, PATCH, etc)
     */
    public function method(
        string $method,
    ): HttpRequest {
        $method  = mb_strtolower($method);
        $builder = new HttpRequest($this->configuration, $this->context, $method, $this->basePath, $this->headers, null, $this->mockedResponses);

        if (in_array($method, ['post', 'put', 'patch', 'delete'], true)) {
            $builder->withHeader('Content-Type', 'application/json');
        }

        $builder->withHeaders($this->headers);

        return $this->lastRequest = $builder;
    }

    /**
     * Inject a series of Psr\Http\Message\ResponseInterface objects into created HttpRequest clients.
     *
     * @codeCoverageIgnore
     *
     * @param ResponseInterface $response
     * @param ?callable         $callback
     * @param ?\Exception       $exception
     */
    public function mockResponse(
        ResponseInterface $response,
        ?callable $callback = null,
        ?\Exception $exception = null,
    ): self {
        $this->mockedResponses[] = (object) [
            'response'  => $response,
            'callback'  => $callback,
            'exception' => $exception,
        ];

        return $this;
    }

    /**
     * Inject a series of Psr\Http\Message\ResponseInterface objects into created HttpRequest clients.
     *
     * @param array<array{response?: ResponseInterface, callback?: callable, exception?: \Exception}|ResponseInterface> $responses an array of ResponseInterface objects, or an array of arrays containing ResponseInterfaces with callbacks
     *
     * @codeCoverageIgnore
     */
    public function mockResponses(
        array $responses,
    ): self {
        foreach ($responses as $response) {
            if ($response instanceof ResponseInterface) {
                $response = ['response' => $response];
            }

            if (! isset($response['response'])) {
                continue;
            }

            $callback = $response['callback'] ?? null;

            if (null !== $callback) {
                $this->mockResponse($response['response'], $callback);

                continue;
            }

            $this->mockResponse($response['response']);
        }

        return $this;
    }
}
