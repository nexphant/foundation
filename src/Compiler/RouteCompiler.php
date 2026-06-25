<?php

namespace Nexphant\Foundation\Compiler;

/**
 * Route Compiler
 *
 * Compiles route metadata for production optimization.
 */
class RouteCompiler
{
    private array $routes = [];

    public function addRoute(string $method, string $path, string $controller, string $action, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function compile(): string
    {
        $routes = var_export($this->routes, true);
        return <<<PHP
<?php

/**
 * Compiled Routes
 * Generated: {$this->timestamp()}
 */

return {$routes};

PHP;
    }

    private function timestamp(): string
    {
        return date('Y-m-d H:i:s');
    }
}
