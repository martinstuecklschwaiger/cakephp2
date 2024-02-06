<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Transform\Rector\Class_\AddAllowDynamicPropertiesAttributeRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/lib',
    ])
    ->withConfiguredRule(AddAllowDynamicPropertiesAttributeRector::class, ["*"])
    ->withPHPVersion(PhpVersion::PHP_83);
