<?php

namespace Woody\Middleware\Exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Woody\Http\Server\Middleware\MiddlewareInterface;

/**
 * Class LogsMiddleware
 *
 * @package Woody\Middleware\Exception
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
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // @todo: log request

        $response = $handler->handle($request);

        // @todo: log response

        return $response;
    }
}
