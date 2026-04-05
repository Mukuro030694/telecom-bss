<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'                         => true,
        '@Symfony'                       => true,
        'array_syntax'                   => ['syntax' => 'short'],
        'ordered_imports'                => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'              => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline'    => true,
        'phpdoc_order'                   => true,
        'declare_strict_types'           => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);