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
 * Reflection scanner — discovers classes in a directory and reads their metadata.
 */
class ReflectionScanner
{
    /** @var array<string, \ReflectionClass> */
    private array $cache = [];

    /**
     * Scan a directory for PHP classes and return ReflectionClass instances.
     *
     * @param string   $directory   Absolute path to scan
     * @param string   $namespace   PSR-4 namespace prefix mapped to $directory
     * @param bool     $recursive   Scan sub-directories
     * @return array<class-string, \ReflectionClass>
     */
    public function scan(string $directory, string $namespace, bool $recursive = true): array
    {
        $result = [];
        $flags  = $recursive
            ? \RecursiveIteratorFlags::CHILD_FIRST | \FilesystemIterator::SKIP_DOTS
            : \FilesystemIterator::SKIP_DOTS;

        $iterator = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS))
            : new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;

            $relative = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relative = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
            $class    = rtrim($namespace, '\\') . '\\' . substr($relative, 0, -4);

            if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
                continue;
            }

            try {
                $ref             = new \ReflectionClass($class);
                $result[$class]  = $ref;
                $this->cache[$class] = $ref;
            } catch (\ReflectionException) {
                // skip unresolvable
            }
        }

        return $result;
    }

    /**
     * Get cached ReflectionClass for a class name.
     */
    public function reflect(string $class): \ReflectionClass
    {
        return $this->cache[$class] ??= new \ReflectionClass($class);
    }

    /**
     * Find all classes in a scan result that have a specific attribute.
     *
     * @template T of object
     * @param array<class-string, \ReflectionClass> $classes
     * @param class-string<T> $attribute
     * @return array<class-string, \ReflectionClass>
     */
    public function withAttribute(array $classes, string $attribute): array
    {
        return array_filter(
            $classes,
            fn(\ReflectionClass $r) => !empty($r->getAttributes($attribute))
        );
    }
}
