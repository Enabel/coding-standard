<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="<?= $testsPath ?>/bootstrap.php"
         colors="true"
         failOnRisky="true"
         failOnWarning="true"
         cacheDirectory=".phpunit.cache"
>
    <php>
        <ini name="display_errors" value="1"/>
        <ini name="error_reporting" value="-1"/>
<?php if ($isSymfony): ?>
        <server name="APP_ENV" value="test" force="true"/>
        <server name="SHELL_VERBOSITY" value="-1"/>
        <server name="SYMFONY_PHPUNIT_REMOVE" value=""/>
        <server name="SYMFONY_PHPUNIT_VERSION" value="11"/>
<?php endif; ?>
    </php>

    <testsuites>
        <testsuite name="Project Test Suite">
            <directory><?= $testsPath ?></directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory suffix=".php"><?= $srcPath ?></directory>
        </include>
    </source>
</phpunit>
