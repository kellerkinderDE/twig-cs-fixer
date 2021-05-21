<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer;

class ConfigResolver
{
    public const MAX_DEPTH_SEARCH = 5;

    public const DEFAULT_CONFIG_NAMES = [
        '.twig_cs',
        '.twig_cs.php',
        '.twig_cs.dist',
        '.twig_cs.dist.php',
    ];

    /**
     * Get specified (or available config)
     * Fallback is a default config
     */
    public function resolve(string $configPath): ?Config
    {
        $projectPath       = getcwd();
        $configIncludePath = sprintf('%s/%s', $projectPath, $configPath);

        if (file_exists($configIncludePath)) {
            $config = require $configIncludePath;

            if ($config instanceof Config) {
                return $config;
            }
        }

        return $this->determineConfig($projectPath);
    }

    private function determineConfig(string $projectPath, int $searchLevel = 0): ?Config
    {
        foreach (self::DEFAULT_CONFIG_NAMES as $configName) {
            $configIncludePath = sprintf('%s/%s', $projectPath, $configName);

            if (file_exists($configIncludePath)) {
                $config = require $configIncludePath;

                if ($config instanceof Config) {
                    return $config;
                }
            }
        }

        if ($searchLevel < self::MAX_DEPTH_SEARCH) {
            return $this->determineConfig(sprintf('%s/..', $projectPath), $searchLevel + 1);
        }

        return null;
    }
}
