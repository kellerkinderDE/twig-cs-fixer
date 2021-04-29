<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer;

use Kellerkinder\TwigCsFixer\FileFixer\AbstractFileFixer;
use Kellerkinder\TwigCsFixer\FileFixer\IndentationFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\AbstractMatchFixer;
use Symfony\Component\Finder\Finder;

class Config
{
    /** @var Finder[] */
    private $finders;
    private $rules       = ['base' => true];
    private $indent      = IndentationFixer::BASE_ELEMENT_INDENT;
    private $projectTest = false;

    /** @var AbstractFileFixer|array */
    private $customFileFixer = [];

    /** @var AbstractMatchFixer|array */
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

    public function getCustomFileFixer()
    {
        return $this->customFileFixer;
    }

    public function setCustomFileFixer(array $customFileFixer): void
    {
        $this->customFileFixer = $customFileFixer;
    }

    public function getCustomMatchFixer()
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
