<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Command;

use function count;
use Kellerkinder\TwigCsFixer\Config;
use Kellerkinder\TwigCsFixer\File;
use Kellerkinder\TwigCsFixer\FileFixer\AbstractFileFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\AbstractMatchFixer;
use Kellerkinder\TwigCsFixer\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Throwable;

class FixCommand extends Command
{
    /** @var AbstractMatchFixer[]|iterable */
    private $matchFixer;

    /** @var AbstractFileFixer[]|iterable */
    private $fileFixer;

    public function __construct(iterable $matchFixer, iterable $fileFixer)
    {
        $this->matchFixer = $matchFixer;
        $this->fileFixer  = $fileFixer;

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('fix')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The path to the config file.')
            ->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The path to scan for twig files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configPath        = $input->getOption('config');
        $configIncludePath = getcwd() . '/' . $configPath;

        // TODO: add handling for default paths
        if (file_exists($configIncludePath)) {
            $config = require $configIncludePath;
        } else {
            // TODO: switch to default config
            throw new FileNotFoundException(sprintf('File "%s" could not be found.', $configPath));
        }

        $files = $this->getFiles($config, $output);
        $this->fixViolations($config, $files, $output);

        return 0;
    }

    protected function getFiles(Config $config, OutputInterface $output): array
    {
        /** @var Finder[] $finders */
        $finders = $config->getFinders();
        $parser  = new Parser();
        $files   = [];

        $output->writeln('Start collecting files');
        foreach ($finders as $finder) {
            $fileIterator = $finder->files();
            $progressBar  = new ProgressBar($output, $fileIterator->count());
            $progressBar->start();

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

            $progressBar->finish();
        }

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
                $fixer->fix($match);
            }

            foreach ($config->getCustomMatchFixer() as $fixer) {
                $fixer->fix($match);
            }

            $matchLine  = $match->getLine();
            $partedLine = $file->getPartedLine($matchLine);
            $file->setPartedLine($matchLine, str_replace($match->getMatch(), $match->getFixedMatch(), $partedLine));
        }
    }

    protected function fixFile(Config $config, File $file): void
    {
        foreach ($this->fileFixer as $fixer) {
            $fixer->fix($file);
        }

        foreach ($config->getCustomFileFixer() as $fixer) {
            $fixer->fix($file);
        }
    }
}
