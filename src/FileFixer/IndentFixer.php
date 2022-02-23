<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\FileFixer;

use Kellerkinder\TwigCsFixer\Config;
use Kellerkinder\TwigCsFixer\File;
use Kellerkinder\TwigCsFixer\Match;

class IndentFixer extends AbstractFileFixer
{
    public const BASE_ELEMENT_INDENT = 4;
    public const BASE_INNER_INDENT   = 2;

    public const HTML_REGEX_OPEN                         = '/<[a-z]+.*>/';
    public const HTML_REGEX_CLOSE                        = '/<\/.+>/';
    public const HTML_REGEX_SELF_CLOSE                   = '/<.*\/>/';
    public const HTML_REGEX_MULTILINE_STATEMENT          = '/<[a-z]+[^>]*\R.*>/';
    public const HTML_REGEX_MULTILINE_OPEN               = '/<[a-z]+/';
    public const HTML_REGEX_MULTILINE_CONTENT            = '/[^<]*[^>]/';
    public const HTML_REGEX_MULTILINE_CLOSE_CONTENT      = '/.*>/';
    public const HTML_REGEX_MULTILINE_CLOSE              = '/>/';
    public const HTML_REGEX_MULTILINE_CLOSE_WITH_CLOSING = '/>\s?<\/[a-zA-Z]+.*>/';

    public const TWIG_REGEX_OPEN                  = '/{%-? .* -?%}/';
    public const TWIG_REGEX_CONTENT               = '/{{-? ?.* ?-?}}/';
    public const TWIG_REGEX_OPEN_SECURE_PART_ONE  = '/\{\%-? end';   // used via sprintf with the opening match
    public const TWIG_REGEX_OPEN_SECURE_PART_TWO  = '.*\-?%\}/';     // used via sprintf with the opening match
    public const TWIG_REGEX_ELSE                  = '/{%-? el[a-z]+.*? -?%}/';
    public const TWIG_REGEX_CLOSE                 = '/{%-? end[a-z]+ ?.*? -?%}/';
    public const TWIG_REGEX_MULTILINE_OPEN        = '/{[%{]-?[^end]*/';
    public const TWIG_REGEX_MULTILINE_CONTENT     = '/[^{%-?]*[^-?%}]/';
    public const TWIG_REGEX_MULTILINE_CLOSE       = '/}? ?(%})|(}})/';
    public const TWIG_REGEX_INNER_TAG_OPEN        = '/\:?\S+=[\'"]{/'; // TODO: there are some inline indents which are not correctly handled (ex.: "[" and "(")
    public const TWIG_REGEX_INNER_TAG_CLOSE       = '/\s?}[\'"]/';
    public const TWIG_REGEX_INNER_TAG_SELFCLOSING = '/\:?\S+=[\'"]{.*}[\'"]/';
    public const TWIG_REGEX_INNER_FUNCTION        = '/.*: [\'"]{{ .+ }}[\'"]/';

    private const LINE_TYPE_OPEN                          = 1;
    private const LINE_TYPE_CONTENT                       = 2;
    private const LINE_TYPE_CLOSE                         = 3;
    private const LINE_TYPE_OPEN_AND_CLOSE                = 4;
    private const LINE_TYPE_SELF_CLOSING                  = 5;
    private const LINE_TYPE_MULTI_LINE_OPEN               = 6;
    private const LINE_TYPE_MULTI_LINE_CONTENT            = 7;
    private const LINE_TYPE_MULTI_LINE_CLOSE              = 8;
    private const LINE_TYPE_MULTI_LINE_CLOSE_WITH_CONTENT = 9;
    private const LINE_TYPE_ELSE                          = 10;
    private const LINE_TYPE_TWIG_OPEN                     = 11;

    private const MULTI_LINE_OPEN_NONE = 20;
    private const MULTI_LINE_OPEN_TWIG = 21;
    private const MULTI_LINE_OPEN_HTML = 22;

    private const MULTI_LINE_INNER_OPEN  = 23;
    private const MULTI_LINE_INNER_CLOSE = 24;

    private const MULTI_LINE_CLOSE_WITH_INNER = 25;

    /** @var int */
    protected $multiLineOpen = self::MULTI_LINE_OPEN_NONE;

    /** @var bool */
    protected $isMultiLine = false;

    /** @var bool */
    protected $isInnerMultiLine = false;

    /** @var Config */
    private $config;

    public static function getRuleName(): string
    {
        return 'IndentFixer';
    }

    public function fix(Config $config, File $file): void
    {
        $this->config           = $config;
        $this->isMultiLine      = false;
        $this->isInnerMultiLine = false;

        $nextIndent         = 0;
        $isScriptBlock      = false;
        $multiLineOpenMatch = null;

        foreach ($file->getLines() as $line) {
            if (preg_match('/<script.*>/', $line->getMatch()) === 1
                && preg_match('/<\/script.*>/', $line->getMatch()) !== 1) {
                $isScriptBlock = !$isScriptBlock;
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

            if ($result === '') {
                $file->setPartedLine($line->getLine(), $result);

                continue;
            }

            $lineType = $this->getLineType($result);

            $this->preResultAdjustments($lineType, $line, $file, $nextIndent, $currentIndent, $multiLineOpenMatch);
            $result = $this->getResult($currentIndent, $lineType, $result);
            $this->postResultAdjustments($lineType, $line, $file, $nextIndent, $multiLineOpenMatch);

            $file->setPartedLine($line->getLine(), $result);
        }
    }

    private function getLineType(string $match): int
    {
        if (strpos($match, '/>')) {
            return self::LINE_TYPE_SELF_CLOSING;
        }

        if ($this->isMultiLine) {
            return $this->getMultiLineType($match);
        }

        if ((preg_match(self::HTML_REGEX_OPEN, $match) === 1 && preg_match(self::HTML_REGEX_CLOSE, $match) === 1)
            || (preg_match(self::TWIG_REGEX_OPEN, $match) === 1 && $this->isTwigClosingTag($match))) {
            return self::LINE_TYPE_OPEN_AND_CLOSE;
        }

        if (preg_match(self::HTML_REGEX_OPEN, $match) === 1) {
            return self::LINE_TYPE_OPEN;
        }

        if (preg_match(self::TWIG_REGEX_ELSE, $match) === 1) {
            return self::LINE_TYPE_ELSE;
        }

        if (preg_match(self::TWIG_REGEX_OPEN, $match) === 1
            && preg_match(self::TWIG_REGEX_CLOSE, $match) !== 1) {
            return self::LINE_TYPE_TWIG_OPEN;
        }

        if (preg_match(self::HTML_REGEX_CLOSE, $match) === 1
            || preg_match(self::TWIG_REGEX_CLOSE, $match) === 1) {
            return self::LINE_TYPE_CLOSE;
        }

        if (preg_match(self::TWIG_REGEX_CONTENT, $match) === 1
            && preg_match(self::HTML_REGEX_MULTILINE_OPEN, $match) !== 1) {
            return self::LINE_TYPE_CONTENT;
        }

        if (preg_match(self::HTML_REGEX_MULTILINE_OPEN, $match) === 1) {
            $this->multiLineOpen = self::MULTI_LINE_OPEN_HTML;

            return self::LINE_TYPE_MULTI_LINE_OPEN;
        }

        if (preg_match(self::TWIG_REGEX_MULTILINE_OPEN, $match) === 1
            && preg_match(self::TWIG_REGEX_CLOSE, $match) !== 1) {
            $this->multiLineOpen = self::MULTI_LINE_OPEN_TWIG;

            return self::LINE_TYPE_MULTI_LINE_OPEN;
        }

        return self::LINE_TYPE_CONTENT;
    }

    private function getMultiLineType(string $match): int
    {
        if ($this->isInnerMultiLine
            && preg_match(self::TWIG_REGEX_INNER_TAG_CLOSE, $match) === 1
            && preg_match(self::TWIG_REGEX_INNER_TAG_SELFCLOSING, $match) !== 1
            && preg_match(self::TWIG_REGEX_INNER_FUNCTION, $match) !== 1
        ) {
            $this->isInnerMultiLine = false;

            if (preg_match(self::HTML_REGEX_MULTILINE_CLOSE_CONTENT, $match) === 1) {
                return self::MULTI_LINE_CLOSE_WITH_INNER;
            }

            return self::MULTI_LINE_INNER_CLOSE;
        }

        if ($this->multiLineOpen === self::MULTI_LINE_OPEN_HTML
            && preg_match(self::HTML_REGEX_MULTILINE_CLOSE, $match) === 1) {
            $this->multiLineOpen = self::MULTI_LINE_OPEN_NONE;

            if (preg_match(self::HTML_REGEX_MULTILINE_CLOSE_CONTENT, $match) === 1) {
                if (preg_match(self::HTML_REGEX_MULTILINE_CLOSE_WITH_CLOSING, $match) === 1) {
                    $this->isMultiLine = false;

                    return self::LINE_TYPE_MULTI_LINE_CLOSE;
                }

                return self::LINE_TYPE_MULTI_LINE_CLOSE_WITH_CONTENT;
            }

            return self::LINE_TYPE_MULTI_LINE_CLOSE;
        }

        if ($this->multiLineOpen === self::MULTI_LINE_OPEN_TWIG
            && preg_match(self::TWIG_REGEX_MULTILINE_CLOSE, $match) === 1) {
            $this->multiLineOpen = self::MULTI_LINE_OPEN_NONE;

            return self::LINE_TYPE_MULTI_LINE_CLOSE;
        }

        if (preg_match(self::TWIG_REGEX_INNER_TAG_OPEN, $match) === 1
            && preg_match(self::TWIG_REGEX_INNER_TAG_SELFCLOSING, $match) !== 1) {
            $this->isInnerMultiLine = true;

            return self::MULTI_LINE_INNER_OPEN;
        }

        if (preg_match(self::TWIG_REGEX_INNER_TAG_OPEN, $match) === 1
            && preg_match(self::TWIG_REGEX_INNER_TAG_SELFCLOSING, $match) !== 1) {
            $this->isInnerMultiLine = true;

            return self::MULTI_LINE_INNER_OPEN;
        }

        if (preg_match(self::TWIG_REGEX_CONTENT, $match) === 1
            && preg_match(self::HTML_REGEX_MULTILINE_OPEN, $match) !== 1) {
            return self::LINE_TYPE_MULTI_LINE_CONTENT;
        }

        if (preg_match(self::TWIG_REGEX_CONTENT, $match) === 1
            && preg_match(self::HTML_REGEX_MULTILINE_OPEN, $match) !== 1) {
            return self::LINE_TYPE_MULTI_LINE_CONTENT;
        }

        if (preg_match(self::HTML_REGEX_MULTILINE_CONTENT, $match) === 1
            || preg_match(self::TWIG_REGEX_MULTILINE_CONTENT, $match) === 1) {
            return self::LINE_TYPE_MULTI_LINE_CONTENT;
        }

        return self::LINE_TYPE_MULTI_LINE_CONTENT;
    }

    private function hasClosingTag(Match $line, array $partedLines, ?Match $multiLineOpenMatch = null): bool
    {
        $trimmedMatch    = $multiLineOpenMatch !== null ? trim($multiLineOpenMatch->getFixedMatch()) : trim($line->getFixedMatch());
        $prefixedMatches = [];

        if ($this->isTagWithoutClosing($trimmedMatch)) {
            return false;
        }

        if (preg_match('/<[a-zA-Z0-9]+.*>?/', $trimmedMatch) > 0) {
            $endPattern      = self::HTML_REGEX_CLOSE;
            $pregPrefixFound = preg_match('/<([a-zA-Z0-9]+).*>?/', $trimmedMatch, $prefixedMatches);

            if ($pregPrefixFound !== 1 && $multiLineOpenMatch !== null) {
                $pregPrefixFound = preg_match('/<([a-zA-Z0-9]+)/', $trimmedMatch, $prefixedMatches);
            }
        } else {
            $endPattern      = self::TWIG_REGEX_CLOSE;
            $pregPrefixFound = preg_match('/{%-? ([a-zA-Z0-9]+).*-?%}/', $trimmedMatch, $prefixedMatches);

            if ($pregPrefixFound !== 1 && $multiLineOpenMatch !== null) {
                $pregPrefixFound = preg_match('/{%-? ([a-zA-Z0-9]+)/', $trimmedMatch, $prefixedMatches);

                if ($pregPrefixFound !== 1) {
                    $pregPrefixFound = preg_match('/{{-? ([a-zA-Z0-9]+)/', $trimmedMatch, $prefixedMatches);
                }
            }
        }

        if ($pregPrefixFound !== 1) {
            return false;
        }

        $plainMatch              = end($prefixedMatches);
        $prefixedPlainStartMatch = sprintf('%s %s', '{%', $plainMatch);
        $prefixedPlainEndMatch   = sprintf('end%s', $plainMatch);

        if (preg_match('/<[a-zA-Z0-9]+.*>?/', $trimmedMatch) > 0) {
            $prefixedPlainStartMatch = sprintf('%s%s', '<', $plainMatch);
            $prefixedPlainEndMatch   = sprintf('%s%s', '/', $plainMatch);
        }

        $opened = 0;
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

    private function getResult(int $currentIndent, int $lineType, string $result): string
    {
        $basePadding             = '';
        $multilineContentPadding = '';

        if ($currentIndent > 0) {
            $basePadding = str_repeat(' ', $this->config->getIndent() * $currentIndent);
        }

        if ($this->isMultiLine && $lineType !== self::LINE_TYPE_MULTI_LINE_OPEN && $lineType !== self::LINE_TYPE_MULTI_LINE_CLOSE) {
            $multilineContentPadding = str_repeat(' ', $this->config->getInnerIndent());

            if ($this->isInnerMultiLine && $lineType !== self::MULTI_LINE_INNER_OPEN && $lineType !== self::MULTI_LINE_INNER_CLOSE) {
                $multilineContentPadding .= str_repeat(' ', $this->config->getInnerIndent());
            }
        }

        return sprintf('%s%s%s', $basePadding, $multilineContentPadding, $result);
    }

    private function isTagWithoutClosing(string $line): bool
    {
        if (preg_match('/{%-? set .+ ?=/', $line) === 1) {
            return true;
        }

        if (preg_match('/{%-? parent -?%}/', $line) === 1) {
            return true;
        }

        return false;
    }

    private function preResultAdjustments(
        int $lineType,
        Match $line,
        File $file,
        int &$nextIndent,
        int &$currentIndent,
        ?Match &$multiLineOpenMatch
    ): void {
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
            $this->isMultiLine  = true;
            $multiLineOpenMatch = $line;
        }

        if ($lineType === self::LINE_TYPE_ELSE) {
            --$currentIndent;
        }
    }

    private function postResultAdjustments(
        int $lineType,
        Match $line,
        File $file,
        int &$nextIndent,
        ?Match &$multiLineOpenMatch
    ): void {
        if ($lineType === self::LINE_TYPE_MULTI_LINE_CLOSE
            || $lineType === self::LINE_TYPE_MULTI_LINE_CLOSE_WITH_CONTENT) {
            if ($this->isMultiLine && $this->hasClosingTag($line, $file->getPartedLines(), $multiLineOpenMatch)) {
                ++$nextIndent;
            }

            $this->isMultiLine  = false;
            $multiLineOpenMatch = null;
        }

        if ($lineType === self::MULTI_LINE_CLOSE_WITH_INNER) {
            $this->isMultiLine = false;
            ++$nextIndent;
        }

        if ($lineType === self::MULTI_LINE_INNER_CLOSE) {
            $this->isInnerMultiLine = false;
        }
    }
}
