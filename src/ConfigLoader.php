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
 * Config loader — loads PHP config files from a directory.
 *
 * Files must return an array. Keyed by filename (without .php extension).
 *
 * Example: config/app.php → Config::get('app.debug')
 */
class ConfigLoader
{
    /** @var array<string, array> */
    private array $data = [];

    /**
     * Load all .php config files from a directory.
     */
    public function loadDirectory(string $dir): void
    {
        foreach (glob(rtrim($dir, '/') . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $this->loadFile($key, $file);
        }
    }

    /**
     * Load a single config file under a key.
     */
    public function loadFile(string $key, string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }
        $data = require $path;
        if (!is_array($data)) {
            throw new \RuntimeException("Config file must return array: {$path}");
        }
        $this->data[$key] = array_merge($this->data[$key] ?? [], $data);
    }

    /**
     * Get a config value using dot notation.
     *
     * @param string $key     e.g. 'app.debug', 'database.default'
     * @param mixed  $default Fallback if key not found
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key, 2);
        $group = $parts[0];
        $rest  = $parts[1] ?? null;

        $data = $this->data[$group] ?? null;
        if ($data === null) return $default;
        if ($rest === null) return $data;

        return $this->dotGet($data, $rest, $default);
    }

    /**
     * Set a config value at runtime using dot notation.
     */
    public function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key, 2);
        $group = $parts[0];
        $rest  = $parts[1] ?? null;

        if ($rest === null) {
            $this->data[$group] = is_array($value) ? $value : [$value];
            return;
        }

        $this->data[$group] ??= [];
        $this->dotSet($this->data[$group], $rest, $value);
    }

    /**
     * Check if a key exists.
     */
    public function has(string $key): bool
    {
        return $this->get($key, '__NEXPHANT_MISS__') !== '__NEXPHANT_MISS__';
    }

    /**
     * Get all loaded config data.
     */
    public function all(): array { return $this->data; }

    // -------------------------------------------------------------------------

    private function dotGet(array $data, string $key, mixed $default): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }
        return $data;
    }

    private function dotSet(array &$data, string $key, mixed $value): void
    {
        $parts = explode('.', $key, 2);
        if (count($parts) === 1) {
            $data[$key] = $value;
            return;
        }
        $data[$parts[0]] ??= [];
        $this->dotSet($data[$parts[0]], $parts[1], $value);
    }
}
