<?php

/**
 * This file is part of the nexphant Framework.
 *
 * (c) nexphant <https://github.com/nexphant>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Nexphant\Foundation\Metadata;

use Nexphant\Foundation\MetadataRegistry;
use Nexphant\Foundation\ReflectionScanner;

/**
 * OpenApiMetadata — generates OpenAPI 3.x metadata from route and model info.
 */
class OpenApiMetadata
{
    public function __construct(
        private readonly MetadataRegistry $registry,
    ) {}

    /**
     * Register an OpenAPI path entry.
     */
    public function registerPath(string $method, string $path, array $spec): void
    {
        $key = strtolower($method) . ':' . $path;
        $this->registry->register('openapi', $key, array_merge([
            'method' => strtolower($method),
            'path'   => $path,
        ], $spec));
    }

    /**
     * Export full OpenAPI 3.x document.
     */
    public function export(array $info = []): array
    {
        $paths = [];
        foreach ($this->registry->group('openapi') as $entry) {
            $method = $entry['method'];
            $path   = $entry['path'];
            unset($entry['method'], $entry['path']);
            $paths[$path][$method] = $entry;
        }

        return [
            'openapi' => '3.0.3',
            'info'    => array_merge([
                'title'   => 'Nexphant API',
                'version' => '1.0.0',
            ], $info),
            'paths'   => $paths,
        ];
    }

    /**
     * Export as JSON string.
     */
    public function toJson(array $info = []): string
    {
        return json_encode($this->export($info), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
