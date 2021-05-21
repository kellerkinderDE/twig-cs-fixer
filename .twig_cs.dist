<?php

declare(strict_types=1);

use Kellerkinder\TwigCsFixer\Config;
use Symfony\Component\Finder\Finder;

require 'vendor/autoload.php';

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.twig')
    ->name('*.html');

return new Config([$finder]);
