<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('vendor-bin')
;

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PER-CS'                                          => true,
        'binary_operator_spaces'                           => ['default' => 'at_least_single_space', 'operators' => ['=>' => 'align']],
        'declare_strict_types'                             => true,
        'no_alias_functions'                               => true,
        'no_useless_sprintf'                               => true,
        'nullable_type_declaration_for_default_null_value' => false, // should be 'true' when we drop support for PHP 7.0 which didn't support nullable types yet
        'ordered_imports'                                  => ['sort_algorithm' => 'alpha'],
        'phpdoc_align'                                     => ['align' => 'vertical'],
        'phpdoc_separation'                                => ['skip_unlisted_annotations' => true],
        'self_accessor'                                    => true,
        'visibility_required'                              => ['elements' => ['property', 'method']], // removed 'const' since we still support PHP 7.0 for now
    ])
    ->setFinder($finder)
;
