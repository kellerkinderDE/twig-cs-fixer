<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer;

class File
{
    /** @var string */
    private $name;

    /** @var string */
    private $path;

    /** @var string */
    private $content;

    /** @var array */
    private $partedLines;

    /** @var Match[] */
    private $matches;

    /** @var bool */
    private $validTwig;

    /** @var bool */
    private $violation;

    public function __construct(string $name, string $path, string $content)
    {
        $this->name    = $name;
        $this->path    = $path;
        $this->content = $content;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getPartedLines(): array
    {
        return $this->partedLines;
    }

    public function setPartedLines(array $partedLines): void
    {
        $this->partedLines = $partedLines;
    }

    public function getMatches(): array
    {
        return $this->matches;
    }

    public function setMatches(array $matches): void
    {
        $this->matches = $matches;
    }

    public function addMatch(Match $match): void
    {
        $this->matches[] = $match;
    }

    public function isValidTwig(): bool
    {
        return $this->validTwig;
    }

    public function setValidTwig(bool $validTwig): void
    {
        $this->validTwig = $validTwig;
    }

    public function hasViolation(): bool
    {
        return $this->violation;
    }

    public function setViolation(bool $violation): void
    {
        $this->violation = $violation;
    }
}
