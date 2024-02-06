<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector;
use Rector\Transform\Rector\Class_\AddAllowDynamicPropertiesAttributeRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/lib',
    ])
    ->withConfiguredRule(AddAllowDynamicPropertiesAttributeRector::class, ["*"])
    ->withRules([
        NullToStrictStringFuncCallArgRector::class,
        Utf8DecodeEncodeToMbConvertEncodingRector::class,
    ])
    ->withPHPVersion(PhpVersion::PHP_83);
