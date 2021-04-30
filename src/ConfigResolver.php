<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer;

class ConfigResolver
{
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

        foreach (self::DEFAULT_CONFIG_NAMES as $configName) {
            $configIncludePath = sprintf('%s/%s', $projectPath, $configPath);

            if (file_exists($configIncludePath)) {
                $config = require $configIncludePath;

                if ($config instanceof Config) {
                    return $config;
                }
            }
        }

        return null;
    }
}
