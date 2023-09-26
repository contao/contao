<?php

declare(strict_types=1);

use Contao\EasyCodingStandard\Fixer\TypeHintOrderFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use SlevomatCodingStandard\Sniffs\Variables\UnusedVariableSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->sets([__DIR__.'/../vendor/contao/easy-coding-standard/config/contao.php']);

    $ecsConfig->skip([
        '*-bundle/contao/*',
        MethodChainingIndentationFixer::class => [
            '*/DependencyInjection/Configuration.php',
        ],
        TypeHintOrderFixer::class,
        UnusedVariableSniff::class => [
            'core-bundle/tests/Session/Attribute/ArrayAttributeBagTest.php',
        ],
    ]);

    $ecsConfig->ruleWithConfiguration(HeaderCommentFixer::class, [
        'header' => "This file is part of Contao.\n\n(c) Leo Feyer\n\n@license LGPL-3.0-or-later",
    ]);

    $ecsConfig->parallel();
    $ecsConfig->lineEnding("\n");

    $parameters = $ecsConfig->parameters();
    $parameters->set(Option::CACHE_DIRECTORY, sys_get_temp_dir().'/ecs_default_cache');
};
