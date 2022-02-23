# Twig codestyle (fixer)

## Implemented Rules
| Function | Implemented |
| --- | --- |
| Indentation | X |
| PipePrefixSpacing | X |
| PipeSuffixSpacing | X |
| SpaceLine | X |
| TrailingSpace | X |
| UppercaseVariables | X |
| @var style |  |
| Your idea |  |

## Setup
1. Go to your project folder and execute `composer require k10r/twig-cs-fixer --dev`
2. Create a file named `.twig_cs` `.twig_cs.dist` or `.twig_cs.dist.php`
    * Fill this file with the [small example content](/.twig_cs-small.example.php) or the [enhanced example content](/.twig_cs-enhanced.example.php)
3. (Optional) Adjust the executed fixers according to your needs
4. Execute the fixer via `vendor/bin/twig-cs-fixer`


## Known issues
* JavaScript (eg. everything inside `<script>`-blocks) is not touched
* Multiple calls for the same function example:`{% set` - some with and some without closing block
* Attribute tags are not sorted
* Multi-Line-Attributes with additional opening tag (eg. `(`, `[`) are not formatted correctly
