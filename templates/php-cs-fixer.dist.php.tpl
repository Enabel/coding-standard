<?php echo "<?php\n"; ?>

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/<?= $srcPath ?>')
<?php if ($testsPath): ?>
    ->in(__DIR__ . '/<?= $testsPath ?>')
<?php endif; ?>
;

return (new PhpCsFixer\Config())
    ->setRules([
<?php if ($isSymfony): ?>
        '@Symfony' => true,
<?php endif; ?>
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'phpdoc_align' => false,
        'phpdoc_to_comment' => false,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
