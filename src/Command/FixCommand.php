<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Command;

use FriendsOfTwig\Twigcs\Config\ConfigResolver;
use FriendsOfTwig\Twigcs\Container;
use Kellerkinder\TwigCsFixer\File;
use Kellerkinder\TwigCsFixer\Fixer\AbstractFixer;
use Kellerkinder\TwigCsFixer\Parser;
use Kellerkinder\TwigCsFixer\Validator\AbstractValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Throwable;

class FixCommand extends Command
{
    /** @var AbstractValidator|iterable */
    private $validators;

    /** @var AbstractFixer|iterable */
    private $fixers;

    public function __construct($validators, iterable $fixers)
    {
        $this->validators = $validators;
        $this->fixers     = $fixers;

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('fix')
            ->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The path to scan for twig files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $resolver = new ConfigResolver(new Container(), getcwd(), [
            'path' => $input->getArgument('paths'),
        ]); // TODO: build directly in project

        $files = $this->buildViolations($resolver, $output);
//        $this->fixViolations($violations, $output); // TODO add handling rto fix violations

        dd($files);
    }

    protected function buildViolations(ConfigResolver $resolver, OutputInterface $output): array
    {
        /** @var Finder[] $finders */
        $finders = $resolver->getFinders();
        $parser  = new Parser();
        $files   = [];

        $output->writeln('Start collecting violations from finder');
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

                    $file = new File($file->getFilename(), $realPath, file_get_contents($realPath));
                    $parser->parseFile($file);

                    /** @var AbstractValidator $validator */
                    foreach ($this->validators as $validator) {
                        $validator->validate($file);
                    }

                    $files[] = $file;
                } catch (Throwable $e) {
                    dd($e);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
        }

        return $files;
    }

    protected function fixViolations(array $violations, OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('Fix violations from here');
        $progressBar = new ProgressBar($output, count($violations));
        $progressBar->start();
        foreach ($violations as $violation) {
            /** @var AbstractFixer $fixer */
            foreach ($this->fixers as $fixer) {
                if (!$fixer->supports($violation)) {
                    continue;
                }

                $fixer->fix($violation);
            }

            $progressBar->advance();
        }
        $progressBar->finish();
    }
}
