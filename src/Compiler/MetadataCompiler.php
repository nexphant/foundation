<?php

namespace Nexphant\Foundation\Compiler;

/**
 * Metadata Compiler
 *
 * Orchestrates compilation of all metadata.
 */
class MetadataCompiler
{
    private string $outputPath;
    private RouteCompiler $routeCompiler;
    private ManifestCompiler $manifestCompiler;

    public function __construct(string $outputPath)
    {
        $this->outputPath = rtrim($outputPath, '/');
        $this->routeCompiler = new RouteCompiler();
        $this->manifestCompiler = new ManifestCompiler();
    }

    public function addRoute(string $method, string $path, string $controller, string $action, array $middleware = []): void
    {
        $this->routeCompiler->addRoute($method, $path, $controller, $action, $middleware);
        $this->manifestCompiler->addRoute([
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middleware' => $middleware,
        ]);
    }

    public function addController(string $class, array $metadata): void
    {
        $this->manifestCompiler->addController($class, $metadata);
    }

    public function addMiddleware(string $class, array $metadata): void
    {
        $this->manifestCompiler->addMiddleware($class, $metadata);
    }

    public function addModel(string $class, array $metadata): void
    {
        $this->manifestCompiler->addModel($class, $metadata);
    }

    public function addDto(string $class, array $metadata): void
    {
        $this->manifestCompiler->addDto($class, $metadata);
    }

    public function compile(): void
    {
        if (!is_dir($this->outputPath)) {
            @mkdir($this->outputPath, 0775, true);
        }

        file_put_contents(
            $this->outputPath . '/routes.php',
            $this->routeCompiler->compile()
        );

        file_put_contents(
            $this->outputPath . '/manifest.php',
            $this->manifestCompiler->compile()
        );
    }

    public function getRouteCompiler(): RouteCompiler
    {
        return $this->routeCompiler;
    }

    public function getManifestCompiler(): ManifestCompiler
    {
        return $this->manifestCompiler;
    }
}
