<?php

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        'concat_with_spaces',
        '-phpdoc_to_comment',
        '-empty_return',
        '-no_empty_lines_after_phpdocs', // see https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/1178
    ])
    ->finder(Symfony\CS\Finder\DefaultFinder::create()
        ->exclude('Fixtures')
        ->exclude('Resources')
        ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    )
;
