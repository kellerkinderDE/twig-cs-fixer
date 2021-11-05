<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\FileFixer;

use Kellerkinder\TwigCsFixer\File;
use Kellerkinder\TwigCsFixer\Match;

class IndentFixer extends AbstractFileFixer
{
    public const BASE_ELEMENT_INDENT = 4;
    public const BASE_INSIDE_INDENT  = 2;

    public const HTML_REGEX_OPEN                = '/<[a-z]+.*>/';
    public const HTML_REGEX_CLOSE               = '/<\/.+>/';
    public const HTML_REGEX_SELF_CLOSE          = '/<.*\/>/';
    public const HTML_REGEX_MULTILINE_STATEMENT = '/<[a-z]+[^>]*\R.*>/';
    public const HTML_REGEX_MULTILINE_OPEN      = '/<[a-z]+/';
    public const HTML_REGEX_MULTILINE_CONTENT   = '/[^<]*[^>]/';
    public const HTML_REGEX_MULTILINE_CLOSE     = '/.*>/';

    public const TWIG_REGEX_OPEN                 = '/{%-? .* -?%}/';
    public const TWIG_REGEX_CONTENT              = '/{{-? .* -?}}/';
    public const TWIG_REGEX_OPEN_SECURE_PART_ONE = '/\{\%-? end';   // used via sprintf with the opening match
    public const TWIG_REGEX_OPEN_SECURE_PART_TWO = '.*\-?%\}/';     // used via sprintf with the opening match
    public const TWIG_REGEX_ELSE                 = '/{%-? el[a-z]+.*? -?%}/';
    public const TWIG_REGEX_CLOSE                = '/{%-? end[a-z]+ ?.*? -?%}/';
    public const TWIG_REGEX_MULTILINE_OPEN       = '/{[%{]-?[^end]*/';
    public const TWIG_REGEX_MULTILINE_CONTENT    = '/[^{%-?]*[^-?%}]/';
    public const TWIG_REGEX_MULTILINE_CLOSE      = '/.*-?(%})|(}})/';

    private const LINE_TYPE_OPEN               = 1;
    private const LINE_TYPE_CONTENT            = 2;
    private const LINE_TYPE_CLOSE              = 3;
    private const LINE_TYPE_OPEN_AND_CLOSE     = 4;
    private const LINE_TYPE_SELF_CLOSING       = 5;
    private const LINE_TYPE_MULTI_LINE_OPEN    = 6;
    private const LINE_TYPE_MULTI_LINE_CONTENT = 7;
    private const LINE_TYPE_MULTI_LINE_CLOSE   = 8;
    private const LINE_TYPE_ELSE               = 9;
    private const LINE_TYPE_TWIG_OPEN          = 10;

    public function fix(File $file): void
    {
        $nextIndent    = 0;
        $isMultiLine   = false;
        $isScriptBlock = false;
        $multiLineOpenMatch = false;

        foreach ($file->getLines() as $line) {
            if ((int) preg_match('/<script.*>/', $line->getMatch()) > 0) {
                $isScriptBlock = !$isScriptBlock;
                $nextIndent += 2;
            }

            if ($isScriptBlock) {
                continue;
            }

            $currentIndent = $nextIndent;
            $result        = $file->getPartedLine($line->getLine());

            if ($result === null) {
                continue;
            }

            $result = trim($result);

            if (empty($result)) {
                $file->setPartedLine($line->getLine(), $result);

                continue;
            }

            $lineType = $this->getLineType($result, $isMultiLine);
            if ($lineType === self::LINE_TYPE_OPEN
                && $this->hasClosingTag($line, $file->getPartedLines())) {
                ++$nextIndent;
            }

            if ($lineType === self::LINE_TYPE_TWIG_OPEN
                && $this->hasClosingTag($line, $file->getPartedLines())) {
                ++$nextIndent;
            }

            if ($lineType === self::LINE_TYPE_CLOSE) {
                --$currentIndent;
                --$nextIndent;
            }

            if ($lineType === self::LINE_TYPE_MULTI_LINE_OPEN) {
                $isMultiLine = true;
                $multiLineOpenMatch = $line;

                if($this->hasClosingTag($line, $file->getPartedLines())) {
                    ++$nextIndent;
                }
            }

            if ($lineType === self::LINE_TYPE_ELSE) {
                --$currentIndent;
            }

            if ($currentIndent > 0) {
                if ($lineType === self::LINE_TYPE_MULTI_LINE_CONTENT) {
                    $result = str_repeat(' ', self::BASE_ELEMENT_INDENT * $currentIndent) . str_repeat(
                            ' ',
                            self::BASE_INSIDE_INDENT
                        ) . $result;
                } else {
                    $result = str_repeat(' ', self::BASE_ELEMENT_INDENT * $currentIndent) . $result;
                }
            }

            if ($lineType === self::LINE_TYPE_MULTI_LINE_CLOSE) {
                if ($this->hasClosingTag($line, $file->getPartedLines(), $multiLineOpenMatch)) {
                    ++$nextIndent;
                }

                $isMultiLine = false;
                $multiLineOpenMatch = null;
            }

            $file->setPartedLine($line->getLine(), $result);
        }
    }

    protected function getLineType(string $match, bool $isMultiLine = false): int
    {
        if (strpos($match, '/>')) {
            return self::LINE_TYPE_SELF_CLOSING;
        }

        if (preg_match(self::TWIG_REGEX_CONTENT, $match) === 1) {
            return $isMultiLine ? self::LINE_TYPE_MULTI_LINE_CONTENT : self::LINE_TYPE_CONTENT;
        }

        if ((preg_match(self::HTML_REGEX_OPEN, $match) === 1 && preg_match(self::HTML_REGEX_CLOSE, $match) === 1)
            || (preg_match(self::TWIG_REGEX_OPEN, $match) === 1 && $this->isTwigClosingTag($match))) {
            return self::LINE_TYPE_OPEN_AND_CLOSE;
        }

        if (preg_match(self::HTML_REGEX_CLOSE, $match) === 1
            || preg_match(self::TWIG_REGEX_CLOSE, $match) === 1) {
            return self::LINE_TYPE_CLOSE;
        }

        if (preg_match(self::TWIG_REGEX_ELSE, $match) === 1) {
            return self::LINE_TYPE_ELSE;
        }

        if ((preg_match(self::HTML_REGEX_MULTILINE_CLOSE, $match) === 1
                || preg_match(self::TWIG_REGEX_MULTILINE_CLOSE, $match) === 1) && $isMultiLine) {
            return self::LINE_TYPE_MULTI_LINE_CLOSE;
        }

        if ((preg_match(self::HTML_REGEX_MULTILINE_CONTENT, $match) === 1
                || preg_match(self::TWIG_REGEX_MULTILINE_CONTENT, $match) === 1) && $isMultiLine) {
            return self::LINE_TYPE_MULTI_LINE_CONTENT;
        }

        if (preg_match(self::HTML_REGEX_OPEN, $match) === 1) {
            return self::LINE_TYPE_OPEN;
        }

        if (preg_match(self::TWIG_REGEX_OPEN, $match) === 1
            && preg_match(self::TWIG_REGEX_CLOSE, $match) !== 1) {
            return self::LINE_TYPE_TWIG_OPEN;
        }

        if (preg_match(self::HTML_REGEX_MULTILINE_OPEN, $match) === 1
            || (preg_match(self::TWIG_REGEX_MULTILINE_OPEN, $match) === 1
                && preg_match(self::TWIG_REGEX_CLOSE, $match) !== 1)) {
            return self::LINE_TYPE_MULTI_LINE_OPEN;
        }

        return $isMultiLine ? self::LINE_TYPE_MULTI_LINE_CONTENT : self::LINE_TYPE_CONTENT;
    }

    private function isFullRegexMatch(string $line, string $regex): bool
    {
        $matches = [];
        $amount  = preg_match($regex, $line, $matches);

        if ($amount <= 0) {
            return false;
        }

        return $line === $matches[0];
    }

    private function hasClosingTag(Match $line, array $partedLines, ?Match $multiLineOpenMatch = null): bool
    {
        $trimmedMatch = $multiLineOpenMatch !== null ? trim($multiLineOpenMatch->getFixedMatch()) : trim($line->getFixedMatch());
        $prefixedMatches = [];

        if (preg_match('/<.*>/', $trimmedMatch) > 0) {
            $endPattern      = self::HTML_REGEX_CLOSE;
            $pregPrefixFound = preg_match('/<([a-zA-Z0-9]+).*>/', $trimmedMatch, $prefixedMatches);
        } else {
            $endPattern      = self::TWIG_REGEX_CLOSE;
            $pregPrefixFound = preg_match('/{%-? ([a-zA-Z0-9]+).*-?%}/', $trimmedMatch, $prefixedMatches);

            if ($pregPrefixFound !== 1 && $multiLineOpenMatch !== null) {
                $pregPrefixFound = preg_match('/{%-? ([a-zA-Z0-9]+)/', $trimmedMatch, $prefixedMatches);
            }
        }

        if ($pregPrefixFound === false) {
            return false;
        }

        $prefixedMatch  = current($prefixedMatches);
        $plainMatch     = $prefixedMatch;
        if(is_array($prefixedMatches)) {
            $plainMatch       = end($prefixedMatches);
        }
        $prefixedPlainStartMatch = sprintf('%s %s','{%', $plainMatch);
        $prefixedPlainEndMatch = sprintf('end%s', $plainMatch);
        $prefixedEndMatch = str_replace($plainMatch, $prefixedPlainEndMatch, $prefixedMatch);
        if (preg_match('/<.*>/', $trimmedMatch) > 0) {
            $prefixedPlainStartMatch = sprintf('%s%s','<', $plainMatch);
            $prefixedPlainEndMatch = sprintf('%s%s', '/', $plainMatch);
            $prefixedEndMatch = str_replace($plainMatch, $prefixedPlainEndMatch, $prefixedMatch);
        }


        $opened           = 0;

        foreach ($partedLines as $lineNumber => $partedLine) {
            if ($lineNumber <= $line->getLine()) {
                continue;
            }

            if (preg_match($endPattern, $partedLine) === 1
                && strpos($partedLine, $prefixedPlainEndMatch) !== false
                && $opened > 0) {
                --$opened;

                continue;
            }

            if (preg_match($endPattern, $partedLine) === 1
                && strpos($partedLine, $prefixedPlainEndMatch) !== false
                && $opened === 0) {
                return true;
            }

            if (strpos($partedLine, $prefixedPlainStartMatch) !== false) {
                ++$opened;
            }
        }

        return false;
    }

    private function isTwigClosingTag(string $match): bool
    {
        $trimmedMatch = trim($match);
        $matches      = [];
        $pregFound    = preg_match('/[a-zA-Z]+/', $trimmedMatch, $matches);

        if ($pregFound === false) {
            return false;
        }

        $pregMatchPattern = sprintf('%s%s%s', self::TWIG_REGEX_OPEN_SECURE_PART_ONE, $matches[0], self::TWIG_REGEX_OPEN_SECURE_PART_TWO);

        return (int) preg_match($pregMatchPattern, $match) > 0;
    }
}
