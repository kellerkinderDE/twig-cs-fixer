<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Command;

use Composer\Autoload\ClassLoader;
use function count;
use Kellerkinder\TwigCsFixer\Config;
use Kellerkinder\TwigCsFixer\ConfigResolver;
use Kellerkinder\TwigCsFixer\File;
use Kellerkinder\TwigCsFixer\FileFixer\AbstractFileFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\AbstractMatchFixer;
use Kellerkinder\TwigCsFixer\Parser;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Throwable;

class FixCommand extends Command
{
    /** @var ConfigResolver */
    private $configResolver;

    /** @var AbstractMatchFixer[]|iterable */
    private $matchFixer;

    /** @var AbstractFileFixer[]|iterable */
    private $fileFixer;

    public function __construct(ConfigResolver $configResolver, iterable $matchFixer, iterable $fileFixer)
    {
        $this->configResolver = $configResolver;
        $this->matchFixer     = $matchFixer;
        $this->fileFixer      = $fileFixer;

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('fix')
            ->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The paths to scan for twig files.')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'The path to the config file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var null|string $configPath */
        $configPath = $input->getOption('config');
        $config     = $this->resolveConfig($configPath);

        if ($config === null) {
            $output->writeln('The configuration file could not be found. Please fill the config parameter');

            return 1;
        }

        $this->mapPaths($config, $input, $output);
        $files = $this->getFiles($config, $output);
        $this->fixViolations($config, $files, $output);

        return 0;
    }

    protected function getFiles(Config $config, OutputInterface $output): array
    {
        $parser = new Parser();
        $files  = [];

        $output->writeln('Start collecting files');
        $progressBar = new ProgressBar($output, count($config->getFiles()));
        $progressBar->start();

        /** @var Finder $finder */
        foreach ($config->getFinders() as $finder) {
            $fileIterator = $finder->files();
            $progressBar->setMaxSteps($progressBar->getMaxSteps() + $fileIterator->count());

            foreach ($fileIterator as $file) {
                try {
                    $realPath = $file->getRealPath();

                    if (!$realPath) {
                        continue;
                    }

                    $fileContent = file_get_contents($realPath);

                    if (!empty($fileContent)) {
                        $file = new File($file->getFilename(), $realPath, $fileContent);
                        $parser->parseFile($file);
                        $files[] = $file;
                    }
                } catch (Throwable $t) {
                    $progressBar->finish();
                    $output->writeln(sprintf('ERROR: %s', $t->getMessage()));
                }

                $progressBar->advance();
            }
        }

        foreach ($config->getFiles() as $realPath) {
            try {
                if (!$realPath) {
                    continue;
                }

                $fileContent = file_get_contents($realPath);

                if (!empty($fileContent)) {
                    $file = new File(basename($realPath), $realPath, $fileContent);
                    $parser->parseFile($file);
                    $files[] = $file;
                }
            } catch (Throwable $t) {
                $progressBar->finish();
                $output->writeln(sprintf('ERROR: %s', $t->getMessage()));
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return $files;
    }

    /** @var File[] $files */
    protected function fixViolations(Config $config, array $files, OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('Fix files from here');
        $progressBar = new ProgressBar($output, count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $this->fixMatches($config, $file);
            $this->fixFile($config, $file);

            $progressBar->advance();
            $path = $file->getPath();

            if ($config->isProjectTest()) {
                $path = dirname($file->getPath()) . '/output/' . $file->getName();
            }

            file_put_contents($path, implode(PHP_EOL, $file->getPartedLines()));
        }

        $progressBar->finish();
    }

    protected function fixMatches(Config $config, File $file): void
    {
        foreach ($file->getMatches() as $match) {
            foreach ($this->matchFixer as $fixer) {
                if ($fixer->isActive($config->getRules())) {
                    $fixer->fix($config, $match);
                }
            }

            foreach ($config->getCustomMatchFixer() as $fixer) {
                if ($fixer->isActive($config->getRules())) {
                    $fixer->fix($config, $match);
                }
            }

            $matchLine  = $match->getLine();
            $partedLine = $file->getPartedLine($matchLine);

            if ($partedLine === null) {
                continue;
            }

            $partedLine = str_replace($match->getMatch(), $match->getFixedMatch(), $partedLine);

            $file->setPartedLine($matchLine, $partedLine);
        }
    }

    protected function fixFile(Config $config, File $file): void
    {
        foreach ($this->fileFixer as $fixer) {
            if ($fixer->isActive($config->getRules())) {
                $fixer->fix($config, $file);
            }
        }

        foreach ($config->getCustomFileFixer() as $fixer) {
            if ($fixer->isActive($config->getRules())) {
                $fixer->fix($config, $file);
            }
        }
    }

    protected function mapPaths(Config $config, InputInterface $input, OutputInterface $output): void
    {
        $paths = $input->getArgument('paths');

        if (!empty($paths)) {
            $config->resetFinders();

            if (is_string($paths)) {
                $this->handlePath($config, $output, $paths);

                return;
            }

            foreach ($paths as $path) {
                $this->handlePath($config, $output, $path);
            }
        }
    }

    private function resolveConfig(?string $configPath, ?bool $isFallback = null): ?Config
    {
        if (is_string($configPath)) {
            $config = $this->configResolver->resolve($configPath);

            if ($config !== null) {
                return $config;
            }

            if ($isFallback) {
                return null;
            }
        }

        foreach (Config::DEFAULT_FILE_NAMES as $fileName) {
            $config = $this->resolveConfig(sprintf('%s/%s', getcwd(), $fileName), true);

            if ($config !== null) {
                return $config;
            }
        }

        $reflection         = new ReflectionClass(ClassLoader::class);
        $reflectionFileName = $reflection->getFileName();

        if ($reflectionFileName === false) {
            return null;
        }

        $vendorDir = dirname($reflectionFileName, 2);

        foreach (Config::DEFAULT_FILE_NAMES as $fileName) {
            $config = $this->resolveConfig(sprintf('%s/../%s', $vendorDir, $fileName), true);

            if ($config !== null) {
                return $config;
            }
        }

        return null;
    }

    private function handlePath(Config $config, OutputInterface $output, string $path): void
    {
        if ($path[0] !== '/') {
            $path = sprintf('%s/%s', getcwd(), $path);
        }

        if (!file_exists($path)) {
            $output->writeln(sprintf('File at path %s is not readable or does not exist.', $path));

            return;
        }

        if (is_dir($path)) {
            $config->addFinder((new Finder())
                ->in(sprintf('%s/%s', getcwd(), $path))
                ->name('*.twig')
                ->name('*.html')
            );

            return;
        }

        if (is_file($path)) {
            $config->addFile($path);
        }
    }
}
