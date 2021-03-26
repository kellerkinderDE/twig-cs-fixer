<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\FileFixer;

use Kellerkinder\TwigCsFixer\File;

// TODO: implement twig open/close handling
class IndentationFixer extends AbstractFileFixer
{
    public const BASE_INDENTATION = 4;

    public const HTML_REGEX_OPEN  = '/<[^\/]+>/';
    public const HTML_REGEX_CLOSE = '/<\/.+>/';

    public function fix(File $file): void
    {
        $openTagCount = 0;
        $partedLines  = $file->getPartedLines();

        foreach ($file->getLines() as $line) {
            $openTagCount -= $this->adjustTagCount(self::HTML_REGEX_CLOSE, $line->getMatch());

            $fixedLine = str_repeat(' ', $openTagCount * self::BASE_INDENTATION) . ltrim($line->getMatch());
            $line->setFixedMatch($fixedLine);

            $partedLines[$line->getLine()] = $line->getFixedMatch();

            $openTagCount += $this->adjustTagCount(self::HTML_REGEX_OPEN, $line->getMatch());
        }

        $file->setPartedLines($partedLines);
    }

    protected function adjustTagCount(string $regex, string $match): int
    {
        return (int) preg_match($regex, $match);
    }
}
