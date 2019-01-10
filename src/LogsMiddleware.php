<?php

namespace Woody\Middleware\Logs;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Woody\Http\Server\Middleware\MiddlewareInterface;

/**
 * Class LogsMiddleware
 *
 * @package Woody\Middleware\Logs
 */
class LogsMiddleware implements MiddlewareInterface
{

    /**
     * Attribute name for deeper middleware.
     */
    const ATTRIBUTE_NAME = 'logger';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * LogsMiddleware constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
            $response = $handler->handle($request->withAttribute(self::ATTRIBUTE_NAME, $this->logger));
        } catch (\Throwable $t) {
            // Bubble exception.
            throw $t;
        } finally {
            $duration = microtime(true) - $startTime;
            $context = [
                'duration' => $duration,
            ];

            if (class_exists('\Woody\Middleware\CorrelationId\CorrelationIdMiddleware')) {
                $attributeName = \Woody\Middleware\CorrelationId\CorrelationIdMiddleware::ATTRIBUTE_NAME;

                if ($correlationId = $request->getAttribute($attributeName)) {
                    $context['correlation-id'] = $correlationId;
                }
            }

            $uri = $request->getUri();

            $this->logger->info(
                sprintf(
                    '%s - - "%s %s %s" %d %d "%s" "%s"',
                    $uri->getHost(),
                    $request->getMethod(),
                    $uri->getPath().($uri->getQuery() ? '?'.$uri->getQuery() : ''),
                    $uri->getScheme(),
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
}
