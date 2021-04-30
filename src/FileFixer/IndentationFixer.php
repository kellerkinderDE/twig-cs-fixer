<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\FileFixer;

use Kellerkinder\TwigCsFixer\File;

// TODO: TWIG - implement else-handling
// TODO: TWIG - implement custom calls (eg. {% set a = 'b' %}
// TODO: HTML - ignore <script> - Content
class IndentationFixer extends AbstractFileFixer
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

    public const TWIG_REGEX_OPEN              = '/{% [^end].* %}/';
    public const TWIG_REGEX_ELSE              = '/{% el[a-z]+ %}/';
    public const TWIG_REGEX_CLOSE             = '/{% end[a-z]+ %}/';
    public const TWIG_REGEX_MULTILINE_OPEN    = '/{%[^end]*/';
    public const TWIG_REGEX_MULTILINE_CONTENT = '/[^{%]*[^%}]/';
    public const TWIG_REGEX_MULTILINE_CLOSE   = '/.*%}/';

    private const LINE_TYPE_OPEN               = 1;
    private const LINE_TYPE_CONTENT            = 2;
    private const LINE_TYPE_CLOSE              = 3;
    private const LINE_TYPE_OPEN_AND_CLOSE     = 4;
    private const LINE_TYPE_SELF_CLOSING       = 5;
    private const LINE_TYPE_MULTI_LINE_OPEN    = 6;
    private const LINE_TYPE_MULTI_LINE_CONTENT = 7;
    private const LINE_TYPE_MULTI_LINE_CLOSE   = 8;

    public function fix(File $file): void
    {
        $nextIndent  = 0;
        $isMultiLine = false;

        foreach ($file->getLines() as $line) {
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

            if ($lineType === self::LINE_TYPE_OPEN) {
                ++$nextIndent;
            }

            if ($lineType === self::LINE_TYPE_CLOSE) {
                --$currentIndent;
                --$nextIndent;
            }

            if ($lineType === self::LINE_TYPE_MULTI_LINE_OPEN) {
                $isMultiLine = true;
            }

            if ($lineType === self::LINE_TYPE_MULTI_LINE_CLOSE) {
                ++$nextIndent;
                $isMultiLine = false;
            }

            if ($currentIndent > 0) {
                if ($lineType === self::LINE_TYPE_MULTI_LINE_CONTENT || $lineType === self::LINE_TYPE_MULTI_LINE_CLOSE) {
                    $result = str_repeat(' ', self::BASE_ELEMENT_INDENT * $currentIndent) . str_repeat(' ', self::BASE_INSIDE_INDENT) . $result;
                } else {
                    $result = str_repeat(' ', self::BASE_ELEMENT_INDENT * $currentIndent) . $result;
                }
            }

            $file->setPartedLine($line->getLine(), $result);
        }
    }

    protected function getLineType(string $match, bool $isMultiLine = false): int
    {
        if (strpos($match, '/>')) {
            return self::LINE_TYPE_SELF_CLOSING;
        }

        if (($this->isRegexMatch($match, self::HTML_REGEX_OPEN) && $this->isRegexMatch($match, self::HTML_REGEX_CLOSE))
            || ($this->isRegexMatch($match, self::TWIG_REGEX_OPEN) && $this->isRegexMatch($match, self::TWIG_REGEX_CLOSE))) {
            return self::LINE_TYPE_OPEN_AND_CLOSE;
        }

        if ($this->isRegexMatch($match, self::HTML_REGEX_OPEN) || $this->isRegexMatch($match, self::TWIG_REGEX_OPEN)) {
            return self::LINE_TYPE_OPEN;
        }

        if ($this->isRegexMatch($match, self::HTML_REGEX_MULTILINE_OPEN) || $this->isRegexMatch($match, self::TWIG_REGEX_MULTILINE_OPEN)) {
            return self::LINE_TYPE_MULTI_LINE_OPEN;
        }

        if (($this->isRegexMatch($match, self::HTML_REGEX_MULTILINE_CLOSE) || $this->isRegexMatch($match, self::TWIG_REGEX_MULTILINE_CLOSE)) && $isMultiLine) {
            return self::LINE_TYPE_MULTI_LINE_CLOSE;
        }

        if (($this->isRegexMatch($match, self::HTML_REGEX_MULTILINE_CONTENT) || $this->isRegexMatch($match, self::TWIG_REGEX_MULTILINE_CONTENT)) && $isMultiLine) {
            return self::LINE_TYPE_MULTI_LINE_CONTENT;
        }

        if ($this->isRegexMatch($match, self::HTML_REGEX_CLOSE) || $this->isRegexMatch($match, self::TWIG_REGEX_CLOSE)) {
            return self::LINE_TYPE_CLOSE;
        }

        return self::LINE_TYPE_CONTENT;
    }

    private function isRegexMatch(string $line, string $regex): bool
    {
        $matches = [];
        $amount  = preg_match($regex, $line, $matches);

        if ($amount <= 0) {
            return false;
        }

        return $line === $matches[0];
    }
}
