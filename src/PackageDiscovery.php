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
 * PackageDiscovery — auto-discovers framework packages from composer.json
 * and registers their service providers.
 */
class PackageDiscovery
{
    private array $providers = [];

    public function __construct(private readonly string $basePath) {}

    /**
     * Scan composer.json / installed packages for nexphant providers.
     */
    public function discover(): array
    {
        $this->providers = [];
        $installedJson   = $this->basePath . '/vendor/composer/installed.json';

        if (!file_exists($installedJson)) {
            return [];
        }

        $installed = json_decode(file_get_contents($installedJson), true, 512, JSON_THROW_ON_ERROR);
        $packages  = $installed['packages'] ?? $installed;

        foreach ($packages as $package) {
            $extra = $package['extra']['nexphant'] ?? [];
            foreach ((array) ($extra['providers'] ?? []) as $provider) {
                $this->providers[] = $provider;
            }
        }

        return $this->providers;
    }

    /**
     * Get all discovered provider class names.
     */
    public function providers(): array
    {
        return $this->providers;
    }

    /**
     * Boot all discovered providers via the container.
     */
    public function boot(Container $container): void
    {
        foreach ($this->providers as $providerClass) {
            if (!class_exists($providerClass)) continue;
            $provider = $container->make($providerClass);
            if (method_exists($provider, 'register')) {
                $provider->register($container);
            }
            if (method_exists($provider, 'boot')) {
                $provider->boot($container);
            }
        }
    }
}
