<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony'               => true,
        '@PSR12'                 => true,
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
        'visibility_required'    => ['property', 'method'], // removed 'const' since we still support PHP 7.0 for now
        'yoda_style'             => null,
    ])
    ->setFinder($finder)
;
