<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer;

class Parser
{
    public function parseFile(File $file): void
    {
        $partedLines = explode(PHP_EOL, $file->getContent());

        if (empty($partedLines)) {
            return;
        }

        $file->setPartedLines($partedLines);
        $this->parseDirectives($file);
    }

    protected function parseDirectives(File $file): void
    {
        $this->parseLines($file);
        $this->parseHtml($file);
        $this->parseAccolades($file);
        $this->parseStatements($file);
    }

    protected function parseLines(File $file): void
    {
        $partedLines = $file->getPartedLines();

        foreach ($partedLines as $partedLine) {
            $match = $this->getDataForMatch($partedLine, $partedLines);

            if ($match !== null) {
                $file->addLine($match);
            }
        }
    }

    protected function parseHtml(File $file): void
    {
        $content     = $file->getContent();
        $partedLines = $file->getPartedLines();
        $html        = [];
        preg_match_all('/<.+>/m', $content, $html);

        if (!$html) {
            return;
        }

        foreach ($html[0] as $html) {
            $match = $this->getDataForMatch($html, $partedLines);

            if ($match !== null) {
                $file->addMatch($match);
            }
        }
    }

    protected function parseAccolades(File $file): void
    {
        $content     = $file->getContent();
        $partedLines = $file->getPartedLines();
        $accolades   = [];
        preg_match_all('/{{.+}}/m', $content, $accolades);

        if (!$accolades) {
            return;
        }

        foreach ($accolades[0] as $accolade) {
            $match = $this->getDataForMatch($accolade, $partedLines);

            if ($match !== null) {
                $file->addMatch($match);
            }
        }
    }

    protected function parseStatements(File $file): void
    {
        $content     = $file->getContent();
        $partedLines = $file->getPartedLines();
        $statements  = [];
        preg_match_all('/{%.+%}/m', $content, $statements);

        if (!$statements) {
            return;
        }

        foreach ($statements[0] as $statement) {
            $match = $this->getDataForMatch($statement, $partedLines);

            if ($match !== null) {
                $file->addMatch($match);
            }
        }
    }

    // TODO: fix error where identical lines get the first occurrence as line/column
    private function getDataForMatch(string $match, array $partedLines): ?Match
    {
        $line       = 0;
        $column     = 0;
        $isInScript = false;
        $emptyLines = 0;

        foreach ($partedLines as $lineNumber => $partedLine) {
            $isInScript = $this->ifInScriptTag($partedLine);

            if (empty($partedLine)) {
                ++$emptyLines;
                $subEmptyLineCounter = 0;
                foreach ($partedLines as $subLineNumber => $subLine) {
                    if (empty($subLine)) {
                        ++$subEmptyLineCounter;

                        if ($subEmptyLineCounter === $emptyLines) {
                            $line = $subLineNumber;

                            break;
                        }
                    }
                }
            }

            if (strpos($partedLine, $match) !== false) {
                $line   = $lineNumber;
                $column = strpos($partedLine, $match);

                break;
            }
        }

        if ($isInScript) {
            return null;
        }

        return new Match($line, $column, $match);
    }

    private function ifInScriptTag(string $line): bool
    {
        $isInScript = false;

        if (strpos($line, '<script')) {
            $isInScript = true;
        }

        if (strpos($line, '</script')) {
            $isInScript = false;
        }

        return $isInScript;
    }
}
