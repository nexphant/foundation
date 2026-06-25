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
 * Container — simple PSR-11-compatible dependency injection container.
 *
 * Supports: bind, singleton, instance, make (auto-wiring), alias.
 */
class Container
{
    private static ?self $instance = null;

    /** @var array<string, \Closure> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /** @var array<string, bool> */
    private array $singletons = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function setInstance(self $container): void
    {
        self::$instance = $container;
    }

    /**
     * Bind an abstract to a concrete factory.
     */
    public function bind(string $abstract, \Closure|string $concrete, bool $singleton = false): void
    {
        $this->bindings[$abstract]   = is_string($concrete)
            ? fn() => $this->make($concrete)
            : $concrete;
        $this->singletons[$abstract] = $singleton;
        unset($this->instances[$abstract]);
    }

    /**
     * Bind as a singleton (resolved once, cached forever).
     */
    public function singleton(string $abstract, \Closure|string $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a pre-built instance.
     */
    public function set(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Register an alias.
     */
    public function alias(string $alias, string $abstract): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolve a binding.
     */
    public function make(string $abstract, array $params = []): mixed
    {
        $abstract = $this->aliases[$abstract] ?? $abstract;

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $resolved = ($this->bindings[$abstract])($this, $params);
            if ($this->singletons[$abstract] ?? false) {
                $this->instances[$abstract] = $resolved;
            }
            return $resolved;
        }

        // Auto-wire via reflection
        return $this->build($abstract, $params);
    }

    /**
     * Check if abstract is bound.
     */
    public function has(string $abstract): bool
    {
        $abstract = $this->aliases[$abstract] ?? $abstract;
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Call a callable with dependency injection.
     */
    public function call(callable $callable, array $params = []): mixed
    {
        $ref  = new \ReflectionFunction(\Closure::fromCallable($callable));
        $args = $this->resolveParams($ref->getParameters(), $params);
        return $callable(...$args);
    }

    // -------------------------------------------------------------------------

    private function build(string $class, array $params = []): mixed
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Cannot resolve [{$class}]: class not found");
        }

        $ref         = new \ReflectionClass($class);
        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $args = $this->resolveParams($constructor->getParameters(), $params);
        return $ref->newInstanceArgs($args);
    }

    /** @param \ReflectionParameter[] $parameters */
    private function resolveParams(array $parameters, array $overrides = []): array
    {
        $args = [];
        foreach ($parameters as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $overrides)) {
                $args[] = $overrides[$name];
                continue;
            }

            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                try {
                    $args[] = $this->make($type->getName());
                    continue;
                } catch (\RuntimeException) {
                    // fall through to default
                }
            }

            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $args[] = null;
            } else {
                throw new \RuntimeException("Cannot resolve parameter \${$name}");
            }
        }
        return $args;
    }
}
