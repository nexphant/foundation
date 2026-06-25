<?php

namespace Nexphant\Foundation;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Annotation Discovery
 *
 * Recursively scans directories for PHP classes with route annotations.
 * No folder convention enforced.
 */
class AnnotationDiscovery
{
    private array $classes = [];

    public function scanDirectories(array $directories): array
    {
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $this->scanDirectory($directory);
        }

        return array_unique($this->classes);
    }

    private function scanDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $classes = $this->extractClasses($file->getPathname());
            foreach ($classes as $class) {
                if ($this->hasRouteAnnotations($class)) {
                    $this->classes[] = $class;
                }
            }
        }
    }

    private function extractClasses(string $file): array
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }

        $tokens = token_get_all($content);
        $classes = [];
        $namespace = '';
        $i = 0;
        $len = count($tokens);

        while ($i < $len) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = $this->extractNamespace($tokens, $i);
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $className = $this->extractClassName($tokens, $i);
                if ($className !== null) {
                    $classes[] = $namespace !== '' ? $namespace . '\\' . $className : $className;
                }
            }

            $i++;
        }

        return $classes;
    }

    private function extractNamespace(array $tokens, int &$i): string
    {
        $namespace = '';
        $i++;

        while (isset($tokens[$i])) {
            $token = $tokens[$i];

            if (is_array($token) && ($token[0] === T_STRING || $token[0] === T_NAME_QUALIFIED)) {
                $namespace .= $token[1];
            } elseif (is_array($token) && $token[0] === T_NS_SEPARATOR) {
                $namespace .= '\\';
            } elseif ($token === ';' || $token === '{') {
                break;
            }

            $i++;
        }

        return $namespace;
    }

    private function extractClassName(array $tokens, int &$i): ?string
    {
        $i++;

        while (isset($tokens[$i])) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_STRING) {
                return $token[1];
            }

            if (!is_array($token) || ($token[0] !== T_WHITESPACE && $token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT)) {
                break;
            }

            $i++;
        }

        return null;
    }

    private function hasRouteAnnotations(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($class);

            // Check class attributes
            $classAttributes = $reflection->getAttributes();
            foreach ($classAttributes as $attribute) {
                if (str_contains($attribute->getName(), 'Route')) {
                    return true;
                }
            }

            // Check method attributes
            foreach ($reflection->getMethods() as $method) {
                $methodAttributes = $method->getAttributes();
                foreach ($methodAttributes as $attribute) {
                    if (str_contains($attribute->getName(), 'Route') || 
                        str_contains($attribute->getName(), 'Get') ||
                        str_contains($attribute->getName(), 'Post') ||
                        str_contains($attribute->getName(), 'Put') ||
                        str_contains($attribute->getName(), 'Patch') ||
                        str_contains($attribute->getName(), 'Delete')) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
}
