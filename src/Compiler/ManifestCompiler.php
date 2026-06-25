<?php

namespace Nexphant\Foundation\Compiler;

/**
 * Manifest Compiler
 *
 * Compiles project metadata for AI tooling, generators, and IDE support.
 */
class ManifestCompiler
{
    private array $controllers = [];
    private array $routes = [];
    private array $middleware = [];
    private array $models = [];
    private array $dto = [];
    private array $events = [];
    private array $commands = [];

    public function addController(string $class, array $metadata): void
    {
        $this->controllers[$class] = $metadata;
    }

    public function addRoute(array $route): void
    {
        $this->routes[] = $route;
    }

    public function addMiddleware(string $class, array $metadata): void
    {
        $this->middleware[$class] = $metadata;
    }

    public function addModel(string $class, array $metadata): void
    {
        $this->models[$class] = $metadata;
    }

    public function addDto(string $class, array $metadata): void
    {
        $this->dto[$class] = $metadata;
    }

    public function addEvent(string $class, array $metadata): void
    {
        $this->events[$class] = $metadata;
    }

    public function addCommand(string $class, array $metadata): void
    {
        $this->commands[$class] = $metadata;
    }

    public function compile(): string
    {
        $manifest = [
            'version' => '1.0.0',
            'generated_at' => date('c'),
            'controllers' => $this->controllers,
            'routes' => $this->routes,
            'middleware' => $this->middleware,
            'models' => $this->models,
            'dto' => $this->dto,
            'events' => $this->events,
            'commands' => $this->commands,
        ];

        $export = var_export($manifest, true);
        return <<<PHP
<?php

/**
 * Application Manifest
 * Generated: {$manifest['generated_at']}
 *
 * This manifest is consumed by:
 * - AI tooling
 * - Code generators
 * - OpenAPI generators
 * - GraphQL schema builders
 * - IDE helpers
 */

return {$export};

PHP;
    }
}
