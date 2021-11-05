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


## Known issues
* `title="{{ variable|stuff(5)|map(user => user.email)|join("<br>") }}">` Will result in an invalid format of the file starting here
* JavaScript (eg. everythin inside `<script>`-blocks) is not touched
