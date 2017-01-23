<?php

$date = date('Y');

$header = <<<EOF
This file is part of Contao.

Copyright (c) 2005-$date Leo Feyer

@license LGPL-3.0+
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('Fixtures')
    ->exclude('Resources')
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        'header_comment',
        'short_array_syntax',
        '-empty_return',
    ])
    ->finder($finder)
;
