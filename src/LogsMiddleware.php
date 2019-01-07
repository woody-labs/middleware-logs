<?php

namespace Woody\Middleware\Logs;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Woody\Http\Server\Middleware\MiddlewareInterface;

/**
 * Class LogsMiddleware
 *
 * @package Woody\Middleware\Logs
 */
class LogsMiddleware implements MiddlewareInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * LogsMiddleware constructor.
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param bool $debug
     *
     * @return bool
     */
    public function isEnabled(bool $debug): bool
    {
        return true;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $t) {
            // Bubble exception.
            throw $t;
        } finally {
            $duration = microtime(true) - $startTime;
            $serverParams = $this->getServerParams($request);
            $context = [
                'duration' => $duration,
            ];

            if (class_exists('\Woody\Middleware\CorrelationId\CorrelationIdMiddleware')) {
                if ($correlationId = $request->getAttribute(\Woody\Middleware\CorrelationId\CorrelationIdMiddleware::ATTRIBUTE_NAME)) {
                    $context['correlation-id'] = $correlationId;
                }
            }

            $uri = $request->getUri();

            $this->logger->info(
                sprintf(
                    '%s - - "%s %s %s" %d %d "%s" "%s"',
                    $uri->getHost(),
                    $serverParams['REQUEST_METHOD'],
                    $uri->getPath().($uri->getQuery() ? '?'.$uri->getQuery() : ''),
                    $serverParams['SERVER_PROTOCOL'],
                    $response->getStatusCode(),
                    $response->getBody()->getSize() ?? 0,
                    $request->getHeaderLine('Referer') ?: '-',
                    str_replace('"', '', $request->getHeaderLine('User-Agent')) ?: '-'
                ),
                $context
            );
        }

        return $response->withHeader('X-Content-Duration', round($duration * 1000));
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return array
     */
    protected function getServerParams(ServerRequestInterface $request): array
    {
        $serverParams = $request->getServerParams();
        $serverParams = array_change_key_case($serverParams, CASE_UPPER);

        foreach ($request->getHeaders() as $name => $headers) {
            $name = 'HTTP_'.strtoupper(str_replace('-', '_', $name));

            foreach ($headers as $header) {
                $serverParams[$name] = $header;
            }
        }

        return $serverParams;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return string
     */
    protected function getRemoteAddr(array $serverParams): ?string
    {
        // Fake request to extract remote addr using proxy and trusted ips.
        $request = new Request([], [], [], [], [], $serverParams);

        return $request->getClientIp() ?? '0.0.0.0';
    }
}
