<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRules(['@PSR12' => true])
    ->setCacheFile(__DIR__ . '/var/cache/php-cs-fixer.cache')
    ->setFinder(Finder::create()->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/config', __DIR__ . '/public'])->name('*.php'));
