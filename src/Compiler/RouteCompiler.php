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

    /**
     * Returns true if the compiled file exists and its embedded hash matches current routes.
     */
    public function isValid(string $compiledFile): bool
    {
        if (!file_exists($compiledFile)) {
            return false;
        }
        $content = file_get_contents($compiledFile);
        if ($content === false) {
            return false;
        }
        if (!preg_match('/\/\/ hash:([a-f0-9]{64})/', $content, $m)) {
            return false;
        }
        return $m[1] === $this->routesHash();
    }

    public function compile(): string
    {
        $routes = var_export($this->routes, true);
        $hash   = $this->routesHash();
        return <<<PHP
<?php

/**
 * Compiled Routes
 * Generated: {$this->timestamp()}
 * // hash:{$hash}
 */

return {$routes};

PHP;
    }

    /**
     * SHA-256 of the canonical JSON representation of current routes.
     */
    private function routesHash(): string
    {
        return hash('sha256', json_encode($this->routes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function timestamp(): string
    {
        return date('Y-m-d H:i:s');
    }
}
