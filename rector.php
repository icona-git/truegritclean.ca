<?php

declare(strict_types=1);

use Netwerkstatt\SilverstripeRector\Set\SilverstripeLevelSetList;
use Netwerkstatt\SilverstripeRector\Set\SilverstripeSetList;
use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app/src',
        __DIR__ . '/app/_config.php',
    ])
    // Force PHP 8.4 rules
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPhpSets(php84: true)
    // Apply Silverstripe 6 specific upgrades
    ->withSets([
        SilverstripeSetList::CODE_STYLE,
        SilverstripeLevelSetList::UP_TO_SS_6_0,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true
    );