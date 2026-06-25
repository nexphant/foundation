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
 * MetadataSerializer — serializes/deserializes metadata to/from array or JSON.
 */
class MetadataSerializer
{
    /**
     * Serialize metadata to a JSON string.
     */
    public function toJson(array $metadata): string
    {
        return json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Deserialize metadata from a JSON string.
     */
    public function fromJson(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Serialize metadata to a PHP array export string (for opcode caching).
     */
    public function toPhpArray(array $metadata): string
    {
        return '<?php return ' . var_export($metadata, true) . ';';
    }

    /**
     * Write PHP array export to a file.
     */
    public function writePhpFile(array $metadata, string $path): void
    {
        file_put_contents($path, $this->toPhpArray($metadata), LOCK_EX);
    }

    /**
     * Load metadata from a PHP array export file.
     */
    public function loadPhpFile(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Metadata file not found: {$path}");
        }
        $data = require $path;
        if (!is_array($data)) {
            throw new \RuntimeException("Metadata file did not return array: {$path}");
        }
        return $data;
    }
}
