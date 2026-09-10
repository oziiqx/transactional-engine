<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/ecs.php',
        __DIR__ . '/rector.php',
    ])
    ->withRootFiles()
    ->withParallel()
    ->withPreparedSets(
        psr12: true,
        common: true,
        strict: true,
    )
    ->withRules([
        DeclareStrictTypesFixer::class,
        StrictComparisonFixer::class,
        StrictParamFixer::class,
    ])
    ->withConfiguredRule(GlobalNamespaceImportFixer::class, [
        'import_classes' => true,
        'import_constants' => false,
        'import_functions' => false,
    ])
    ->withConfiguredRule(OrderedImportsFixer::class, [
        'imports_order' => ['class', 'function', 'const'],
        'sort_algorithm' => 'alpha',
    ])
    // Keep hand-written @param/@return refinements — they carry non-empty-string,
    // list<>, positive-int etc. that PHPStan level 10 relies on.
    ->withConfiguredRule(NoSuperfluousPhpdocTagsFixer::class, [
        'allow_mixed' => true,
        'allow_unused_params' => true,
        'remove_inheritdoc' => false,
    ])
    ->withSkip([
        // These reformat Pest expectation chains and long fluent calls into
        // something far less readable than the original.
        \Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer::class,
        \PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer::class,
        \Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer::class,
        // Framework-generated config reference helper — not ours to style.
        __DIR__ . '/config/reference.php',
    ]);
