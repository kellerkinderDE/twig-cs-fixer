<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer;

use Kellerkinder\TwigCsFixer\FileFixer\AbstractFileFixer;
use Kellerkinder\TwigCsFixer\FileFixer\IndentFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\AbstractMatchFixer;
use Symfony\Component\Finder\Finder;

class Config
{
    /** @var Finder[] */
    private $finders;

    /** @var array */
    private $files = [];

    /** @var array */
    private $rules = ['base' => true];

    /** @var int */
    private $indent = IndentFixer::BASE_ELEMENT_INDENT;

    /** @var bool */
    private $projectTest = false;

    /** @var AbstractFileFixer[]|array */
    private $customFileFixer = [];

    /** @var AbstractMatchFixer[]|array */
    private $customMatchFixer = [];

    public function __construct(array $finders, array $rules = [], ?int $indent = null)
    {
        $this->finders = $finders;

        if (!empty($rules)) {
            $this->rules = $rules;
        }

        if ($indent !== null) {
            $this->indent = $indent;
        }
    }

    public function getFinders(): array
    {
        return $this->finders;
    }

    public function setFinders(array $finders): void
    {
        $this->finders = $finders;
    }

    public function resetFinders(): void
    {
        $this->finders = [];
    }

    public function addFinder(Finder $finder): void
    {
        $this->finders[] = $finder;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): void
    {
        $this->files = $files;
    }

    public function addFile(string $file): void
    {
        $this->files[] = $file;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    public function getIndent(): int
    {
        return $this->indent;
    }

    public function setIndent(int $indent): void
    {
        $this->indent = $indent;
    }

    public function isProjectTest(): bool
    {
        return $this->projectTest;
    }

    public function setProjectTest(bool $projectTest): void
    {
        $this->projectTest = $projectTest;
    }

    public function getCustomFileFixer(): array
    {
        return $this->customFileFixer;
    }

    public function setCustomFileFixer(array $customFileFixer): void
    {
        $this->customFileFixer = $customFileFixer;
    }

    public function getCustomMatchFixer(): array
    {
        return $this->customMatchFixer;
    }

    public function setCustomMatchFixer(array $customMatchFixer): void
    {
        $this->customMatchFixer = $customMatchFixer;
    }

    private function addCustomFileFixer(AbstractFileFixer $fixer): self
    {
        $this->customFileFixer[] = $fixer;

        return $this;
    }

    private function addCustomMatchFixer(AbstractMatchFixer $fixer): self
    {
        $this->customMatchFixer[] = $fixer;

        return $this;
    }
}
