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
 * Environment loader — reads .env files and populates $_ENV / getenv().
 *
 * Follows the standard KEY=VALUE format with comments and quoted strings.
 * Does not override existing env vars by default.
 */
class EnvLoader
{
    private array $loaded = [];

    /**
     * Load a .env file.
     *
     * @param bool $override  Override existing variables
     * @throws \RuntimeException if file not found
     */
    public function load(string $path, bool $override = false): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(".env file not found: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and blank lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Split on first =
            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Strip inline comments
            $value = $this->stripComment($value);

            // Unquote
            $value = $this->unquote($value);

            if (!$override && (isset($_ENV[$key]) || getenv($key) !== false)) {
                continue;
            }

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
            $this->loaded[$key] = $value;
        }
    }

    /**
     * Get a loaded variable (by key).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->loaded[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Get all loaded variables from this loader instance.
     */
    public function all(): array { return $this->loaded; }

    // -------------------------------------------------------------------------

    private function stripComment(string $value): string
    {
        // Only strip if not inside quotes
        if (str_starts_with($value, '"') || str_starts_with($value, "'")) {
            return $value;
        }
        $pos = strpos($value, ' #');
        return $pos !== false ? rtrim(substr($value, 0, $pos)) : $value;
    }

    private function unquote(string $value): string
    {
        if (strlen($value) >= 2) {
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                return substr($value, 1, -1);
            }
        }
        return $value;
    }
}
