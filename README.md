# Middleware Logs

This middleware logs requests.


## Implementation

It is highly suggested to include `monolog/monolog` in your project.

````php
// @todo: generate request

// Initialize logger.
$logHandler = new ErrorLogHandler();
$memoryUsageProcessor = new MemoryUsageProcessor(true, false);
$logger = new Logger('http', [$logHandler], [$memoryUsageProcessor]);

// Dispatch request into middleware stack.
$dispatcher = new Dispatcher();
$dispatcher->pipe(new CorrelationIdMiddleware());
$dispatcher->pipe(new LogsMiddleware($logger));
$dispatcher->pipe(new ExceptionMiddleware());
$dispatcher->pipe(new MyAddMiddleware());

// @todo: add other middleware

$response = $dispatcher->handle($request);
````

