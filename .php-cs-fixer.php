<?php

use PhpCsFixer\Finder as PhpCsFixerFinder;
use PhpCsFixer\Config as PhpCsFixerConfig;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = PhpCsFixerFinder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('vendor-bin')
;

$config = new PhpCsFixerConfig();
return $config
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS'                                          => true,
        'binary_operator_spaces'                           => ['default' => 'at_least_single_space', 'operators' => ['=>' => 'align']],
        'declare_strict_types'                             => true,
        'no_alias_functions'                               => true,
        'no_unused_imports'                                => true,
        'no_useless_sprintf'                               => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_imports'                                  => ['sort_algorithm' => 'alpha'],
        'phpdoc_align'                                     => ['align' => 'vertical'],
        'phpdoc_separation'                                => ['skip_unlisted_annotations' => true],
        'self_accessor'                                    => true,
        'trailing_comma_in_multiline'                      => ['after_heredoc' => true, 'elements' => ['arrays']], // remove this line when we drop support for PHP < 8.0
    ])
    ->setFinder($finder)
;
