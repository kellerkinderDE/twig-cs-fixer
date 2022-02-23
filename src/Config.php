<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer;

use Kellerkinder\TwigCsFixer\FileFixer\AbstractFileFixer;
use Kellerkinder\TwigCsFixer\FileFixer\IndentFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\AbstractMatchFixer;
use Symfony\Component\Finder\Finder;

class Config
{
    public const DEFAULT_FILE_NAMES = ['.twig_cs', '.twig_cs.dist', '.twig_cs.dist.php'];

    private const DEFAULT_RULES = [
        'IndentFixer'        => true,
        'PipePrefixSpacing'  => true,
        'PipeSuffixSpacing'  => true,
        'SelfClosingSpacing' => true,
        'SpaceLine'          => true,
        'TrailingSpace'      => true,
    ];

    /** @var Finder[] */
    private $finders;

    /** @var array */
    private $files = [];

    /** @var array */
    private $rules = self::DEFAULT_RULES;

    /** @var int */
    private $indent = IndentFixer::BASE_ELEMENT_INDENT;

    /** @var int */
    private $innerIndent = IndentFixer::BASE_INNER_INDENT;

    /** @var bool */
    private $projectTest = false;

    /** @var AbstractFileFixer[]|array */
    private $customFileFixer = [];

    /** @var AbstractMatchFixer[]|array */
    private $customMatchFixer = [];

    public function __construct(array $finders, array $data = [])
    {
        $this->finders = $finders;

        foreach ($data as $propertyKey => $propertyValue) {
            if (!is_string($propertyKey)) {
                continue;
            }

            if ($propertyKey === 'rules') {
                $this->rules = array_merge($this->rules, $propertyValue);

                continue;
            }

            if (property_exists($this, $propertyKey)) {
                $this->$propertyKey = $propertyValue;
            }
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

    public function getInnerIndent(): int
    {
        return $this->innerIndent;
    }

    public function setInnerIndent(int $innerIndent): void
    {
        $this->innerIndent = $innerIndent;
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
