parameters:
    level: <?= $phpstanLevel ?>

    paths:
        - <?= $srcPath ?>

<?php if ($testsPath): ?>
        - <?= $testsPath ?>

<?php endif; ?>
    excludePaths:
<?php if ($testsPath): ?>
        - <?= $testsPath ?>/bootstrap.php
<?php endif; ?>

    tmpDir: var/cache/phpstan

    parallel:
        maximumNumberOfProcesses: 4

    inferPrivatePropertyTypeFromConstructor: true
    treatPhpDocTypesAsCertain: false
    checkUninitializedProperties: true
    checkDynamicProperties: true
    checkImplicitMixed: true

    ignoreErrors:
        - '#^.*generic type.*$#'

    reportUnmatchedIgnoredErrors: false
