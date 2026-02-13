<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Functional\PageController;

use Contao\ContentModel;

class TestFragmentController
{
    public function __construct(
        private readonly ContentModel $contentModel,
        private readonly string $column,
    ) {
    }

    public function generate(): string
    {
        $GLOBALS['TL_BODY'][] = '<script id="test-fragment-script"></script>';
        $GLOBALS['TL_STYLE_SHEETS'][] = '<link rel="stylesheet" href="test-fragment-styles.css">';

        return "[content from test fragment controller for tl_content.{$this->contentModel->id} in $this->column]";
    }
}
