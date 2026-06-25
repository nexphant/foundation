<?php

/**
 * This file is part of the nexphant Framework.
 *
 * (c) nexphant <https://github.com/nexphant>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Nexphant\Foundation;

/**
 * Global exception handler — catches unhandled exceptions and formats responses.
 *
 * Supports JSON/HTML responses and custom error formatters.
 */
class ExceptionHandler
{
    private array $dontReport = [];
    private array $formatters = [];
    private bool  $debug      = false;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Register the handler with PHP.
     */
    public function register(): void
    {
        set_exception_handler([$this, 'handle']);
    }

    /**
     * Handle an exception — log it and render a response.
     */
    public function handle(\Throwable $e): void
    {
        if (!$this->shouldntReport($e)) {
            $this->report($e);
        }

        $this->render($e);
    }

    /**
     * Report exception to logs.
     */
    public function report(\Throwable $e): void
    {
        error_log(sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s",
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));
    }

    /**
     * Render exception as HTTP response.
     */
    public function render(\Throwable $e): void
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        if ($this->isJsonRequest()) {
            $this->renderJson($e, $statusCode);
        } else {
            $this->renderHtml($e, $statusCode);
        }
    }

    /**
     * Add exception classes that should not be reported.
     */
    public function dontReport(string ...$exceptions): self
    {
        $this->dontReport = array_merge($this->dontReport, $exceptions);
        return $this;
    }

    /**
     * Register a custom formatter for a specific exception type.
     */
    public function formatter(string $exception, callable $formatter): self
    {
        $this->formatters[$exception] = $formatter;
        return $this;
    }

    // -------------------------------------------------------------------------

    private function shouldntReport(\Throwable $e): bool
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) return true;
        }
        return false;
    }

    private function isJsonRequest(): bool
    {
        return isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
    }

    private function renderJson(\Throwable $e, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json');

        $data = ['error' => $e->getMessage()];
        if ($this->debug) {
            $data['exception'] = $e::class;
            $data['file']      = $e->getFile();
            $data['line']      = $e->getLine();
            $data['trace']     = explode("\n", $e->getTraceAsString());
        }

        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    private function renderHtml(\Throwable $e, int $status): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');

        if ($this->debug) {
            echo $this->debugTemplate($e);
        } else {
            echo $this->productionTemplate($status);
        }
    }

    private function debugTemplate(\Throwable $e): string
    {
        $class   = htmlspecialchars($e::class);
        $message = htmlspecialchars($e->getMessage());
        $file    = htmlspecialchars($e->getFile());
        $line    = $e->getLine();
        $trace   = htmlspecialchars($e->getTraceAsString());

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Error</title></head>
<body style="font-family:monospace;padding:2rem;background:#f8d7da;color:#721c24;">
<h1>{$class}</h1>
<p><strong>Message:</strong> {$message}</p>
<p><strong>File:</strong> {$file}:{$line}</p>
<pre>{$trace}</pre>
</body>
</html>
HTML;
    }

    private function productionTemplate(int $status): string
    {
        $message = $status === 404 ? 'Page Not Found' : 'Internal Server Error';
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{$message}</title></head>
<body style="text-align:center;padding:5rem;font-family:sans-serif;">
<h1>{$status}</h1>
<p>{$message}</p>
</body>
</html>
HTML;
    }
}
