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
        'declare_strict_types'   => true,
        'ordered_imports'        => ['sort_algorithm' => 'alpha'],
        'phpdoc_align'           => false,
        'phpdoc_separation'      => false,
        'phpdoc_summary'         => false,
        'yoda_style'             => null,
    ])
    ->setFinder($finder)
;
