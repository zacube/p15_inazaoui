<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSets([
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_60,
    ])
    ->withRules([
        AnnotationToAttributeRector::class,
    ]);
