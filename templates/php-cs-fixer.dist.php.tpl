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
        'declare_strict_types' => ['preserve_existing_declaration' => true],
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'phpdoc_align' => false,
        'phpdoc_to_comment' => false, // Keep PHPStan type annotations
        'header_comment' => [
            'header' => <<<EOF
This file is part of the <?= $projectName ?>.
Copyright (c) Enabel <https://github.com/Enabel>
For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF
        ]
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setCacheFile('tools/php-cs-fixer/.php-cs-fixer.cache')
;
