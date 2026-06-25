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
 * MetadataCache — file-based cache for compiled metadata.
 *
 * Stores serialized metadata arrays to avoid repeated reflection on every request.
 */
class MetadataCache
{
    public function __construct(private readonly string $cachePath)
    {
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
    }

    /**
     * Store metadata under a cache key.
     */
    public function put(string $key, array $data): void
    {
        file_put_contents($this->file($key), serialize($data), LOCK_EX);
    }

    /**
     * Retrieve cached metadata, or null if not found / stale.
     *
     * @param int $maxAge  Maximum age in seconds (0 = no TTL check)
     */
    public function get(string $key, int $maxAge = 0): ?array
    {
        $file = $this->file($key);
        if (!file_exists($file)) return null;

        if ($maxAge > 0 && (time() - filemtime($file)) > $maxAge) {
            unlink($file);
            return null;
        }

        $data = @unserialize(file_get_contents($file));
        return is_array($data) ? $data : null;
    }

    /**
     * Check if a key exists in cache.
     */
    public function has(string $key): bool
    {
        return file_exists($this->file($key));
    }

    /**
     * Remove a single key.
     */
    public function forget(string $key): void
    {
        $file = $this->file($key);
        if (file_exists($file)) unlink($file);
    }

    /**
     * Clear all cached metadata.
     */
    public function flush(): void
    {
        foreach (glob($this->cachePath . '/*.meta') ?: [] as $file) {
            unlink($file);
        }
    }

    // -------------------------------------------------------------------------

    private function file(string $key): string
    {
        return $this->cachePath . '/' . md5($key) . '.meta';
    }
}
