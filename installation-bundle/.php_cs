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
        'header_comment',
        'concat_with_spaces',
        'short_array_syntax',
        '-phpdoc_to_comment',
        '-empty_return',
        '-concat_without_spaces',
    ])
    ->finder($finder)
;
