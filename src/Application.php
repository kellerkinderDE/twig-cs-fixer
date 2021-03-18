<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer;

use Kellerkinder\TwigCsFixer\Command\FixCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Throwable;

class Application extends BaseApplication
{
    public const NAME    = 'twig-cs-fixer';
    public const VERSION = 'v0.0.1';

    /** @var ContainerBuilder */
    private $container;

    public function __construct(bool $singleCommand = false)
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->setCompiledContainer();

        try {
            $fixCommand = $this->container->get(FixCommand::class);

            $this->add($fixCommand);
            $this->setDefaultCommand($fixCommand->getName(), $singleCommand);
        } catch (Throwable $t) {
            dd($t);
        }
    }

    protected function setCompiledContainer(): void
    {
        $containerBuilder = new ContainerBuilder();
        $loader           = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__));

        try {
            $loader->load('DependencyInjection/services.xml');
            $containerBuilder->compile();
        } catch (Throwable $t) {
            dd($t);
        }

        $this->container = $containerBuilder;
    }
}
