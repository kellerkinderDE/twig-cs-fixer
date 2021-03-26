<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Command;

use function count;
use FriendsOfTwig\Twigcs\Config\ConfigResolver;
use FriendsOfTwig\Twigcs\Container;
use Kellerkinder\TwigCsFixer\File;
use Kellerkinder\TwigCsFixer\FileFixer\AbstractFileFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\AbstractMatchFixer;
use Kellerkinder\TwigCsFixer\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The path to scan for twig files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resolver = new ConfigResolver(new Container(), getcwd(), [
            'path' => $input->getArgument('paths'),
        ]); // TODO: build directly in project

        $files = $this->getFiles($resolver, $output);
        $this->fixViolations($files, $output); // TODO add handling to fix violations

        return 0;
    }

    protected function getFiles(ConfigResolver $resolver, OutputInterface $output): array
    {
        /** @var Finder[] $finders */
        $finders = $resolver->getFinders();
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
    protected function fixViolations(array $files, OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('Fix files from here');
        $progressBar = new ProgressBar($output, count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $partedLines = $file->getPartedLines();

            $this->fixMatches($file, $partedLines);
            $this->fixFile($file, $partedLines);

            $progressBar->advance();

            file_put_contents($file->getPath(), implode(PHP_EOL, $partedLines));
        }

        $progressBar->finish();
    }

    protected function fixMatches(File $file, array &$partedLines): void
    {
        foreach ($file->getMatches() as $match) {
            foreach ($this->matchFixer as $fixer) {
                $fixer->fix($match);
            }
            $matchLine = $match->getLine();

            $partedLines[$matchLine] = str_replace($match->getMatch(), $match->getFixedMatch(), $partedLines[$matchLine]);
        }
    }

    protected function fixFile(File $file, array &$partedLines): void
    {
        foreach ($this->fileFixer as $fixer) {
            $fixer->fix($file);
        }

        // TODO: check why file isn't written
        file_put_contents($file->getPath(), implode(PHP_EOL, $file->getPartedLines()));
    }
}
