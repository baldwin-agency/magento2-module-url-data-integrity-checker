<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony'               => true,
        'array_syntax'           => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => null, 'operators' => ['=>' => 'align']],
        'concat_space'           => ['spacing' => 'one'],
        'declare_strict_types'   => true,
        'no_alias_functions'     => true,
        'no_useless_sprintf'     => true,
        'ordered_imports'        => [
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
            'sort_algorithm' => 'alpha'
        ],
        'phpdoc_align'           => ['align' => 'left'],
        'phpdoc_summary'         => false,
        'self_accessor'          => true,
        'single_line_throw'      => false,
        'yoda_style'             => null,
    ])
    ->setFinder($finder)
;
