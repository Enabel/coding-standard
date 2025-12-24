<?php echo "<?php\n"; ?>

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\Set\ValueObject\SetList;
<?php if ($isSymfony): ?>
use Rector\Symfony\Set\SymfonySetList;
<?php endif; ?>

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/<?= $srcPath ?>',
<?php if ($testsPath): ?>
        __DIR__ . '/<?= $testsPath ?>',
<?php endif; ?>
    ])
    ->withPhpSets(php<?= $phpVersionNumber ?>: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
<?php if ($isSymfony): ?>
        SymfonySetList::SYMFONY_CODE_QUALITY,
<?php endif; ?>
    ])
    ->withSkip([
        ExplicitNullableParamTypeRector::class,
    ])
<?php if ($isSymfony): ?>
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
<?php endif; ?>
;
