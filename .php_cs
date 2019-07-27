<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony'               => true,
        'array_syntax'           => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => null],
        'concat_space'           => ['spacing' => 'one'],
        'ordered_imports'        => ['sort_algorithm' => 'alpha'],
        'phpdoc_summary'         => false,
        'phpdoc_separation'      => false,
        'phpdoc_align'           => false,
        'yoda_style'             => null,
        'declare_strict_types'   => true,
    ])
    ->setFinder($finder)
;
