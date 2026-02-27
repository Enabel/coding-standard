parameters:
    level: <?= $phpstanLevel ?>

    paths:
        - <?= $srcPath ?>

    excludePaths:
<?php if ($testsPath): ?>
        - <?= $testsPath ?>/bootstrap.php
<?php endif; ?>

    # Performance
<?php if ($isSymfony): ?>
    tmpDir: var/cache/phpstan
<?php else: ?>
    tmpDir: /tmp/phpstan
<?php endif; ?>
    parallel:
    maximumNumberOfProcesses: 4

    # Inférence de types
    inferPrivatePropertyTypeFromConstructor: true
    treatPhpDocTypesAsCertain: false

    # Détection des erreurs cachées
    checkUninitializedProperties: true
    checkDynamicProperties: false # Set to true to be stricter
    checkImplicitMixed: false # Set to true to be stricter

<?php if ($isSymfony): ?>
    symfony:
        constantHassers: false
        containerXmlPath: var/cache/dev/App_KernelDevDebugContainer.xml
        consoleApplicationLoader: tools/phpstan/console-application.php

    doctrine:
        repositoryClass: Doctrine\ORM\EntityRepository
        objectManagerLoader: tools/phpstan/object-manager.php
        allCollectionsSelectable: false
<?php endif; ?>


    reportUnmatchedIgnoredErrors: false
    ignoreErrors:
        -
            identifier: missingType.generics
<?php if ($isSymfony): ?>
        -
            identifier: doctrine.associationType
            path: src/Entity
<?php endif; ?>
