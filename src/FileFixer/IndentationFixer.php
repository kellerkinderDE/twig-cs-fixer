<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\FileFixer;

use Kellerkinder\TwigCsFixer\File;

// TODO: implement twig open/close handling
class IndentationFixer extends AbstractFileFixer
{
    public const BASE_INDENTATION = 4;

    public const HTML_REGEX_OPEN       = '/<[a-z]+.*>/';
    public const HTML_REGEX_CLOSE      = '/<\/.+>/';
    public const HTML_REGEX_SELF_CLOSE = '/<.*\/>/';

    public function fix(File $file): void
    {
        $openTagCount = 0;
        $partedLines  = $file->getPartedLines();

        foreach ($file->getLines() as $line) {
            $originalOpenTagCount = $openTagCount;
            $match                = $line->getFixedMatch();
            $openTagCount         = $this->handleHtmlTags($openTagCount, $match);

            if (!empty($match)) {
                $fixedLine = str_repeat(' ', $openTagCount * self::BASE_INDENTATION) . ltrim($match);

                if ($originalOpenTagCount < $openTagCount) {
                    $fixedLine = str_repeat(' ', $originalOpenTagCount * self::BASE_INDENTATION) . ltrim($match);
                }

                $line->setFixedMatch($fixedLine);
                $partedLines[$line->getLine()] = $fixedLine;
            }
        }

        $file->setPartedLines($partedLines);
    }

    protected function handleHtmlTags(int $openTagCount, string $match): int
    {
        if (strpos($match, '/>')) {
            return $openTagCount;
        } elseif (preg_match(self::HTML_REGEX_OPEN, $match) > 0 && preg_match(self::HTML_REGEX_CLOSE, $match) > 0) {
            return $openTagCount;
        } elseif (preg_match(self::HTML_REGEX_OPEN, $match) > 0) {
            return $openTagCount + 1;
        } elseif (preg_match(self::HTML_REGEX_CLOSE, $match) > 0) {
            return $openTagCount - 1;
        }

        return $openTagCount;
    }
}
