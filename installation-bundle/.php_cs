<?php

$date = date('Y');

$header = <<<EOF
This file is part of Contao.

Copyright (c) 2005-$date Leo Feyer

@license LGPL-3.0+
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__ . '/src')
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        '-psr0',
        'header_comment',
        'short_array_syntax',
        '-empty_return',
    ])
    ->finder($finder)
;
