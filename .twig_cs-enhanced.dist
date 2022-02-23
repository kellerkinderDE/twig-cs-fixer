<?php

declare(strict_types=1);

use Kellerkinder\TwigCsFixer\Config;
use Kellerkinder\TwigCsFixer\FileFixer\IndentFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\PipePrefixSpacingFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\PipeSuffixSpacingFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\SelfClosingSpacingFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\SpaceLineFixer;
use Kellerkinder\TwigCsFixer\MatchFixer\TrailingSpaceFixer;
use Symfony\Component\Finder\Finder;

require 'vendor/autoload.php';

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.twig')
    ->name('*.html');

return new Config([$finder], [
    'indent' => '7',
    'innerIndent' => '3',
    'rules' => [
        IndentFixer::getRuleName() => false,
        PipePrefixSpacingFixer::getRuleName() => true,
        PipeSuffixSpacingFixer::getRuleName() => false,
        SelfClosingSpacingFixer::getRuleName() => true,
        SpaceLineFixer::getRuleName() => false,
        TrailingSpaceFixer::getRuleName() => true,
    ]
]);
