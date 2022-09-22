<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'tests']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PSR2' => true,
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'yoda_style' => false,
        'concat_space' => false,
        'increment_style' => ['style' => 'post'],
        'function_declaration' => ['closure_function_spacing' => 'one'],
        'not_operator_with_successor_space' => false,
        'single_line_throw' => false,
        'no_superfluous_phpdoc_tags' => true,
        'no_break_comment' => false,
    ])
    ->setFinder($finder);
