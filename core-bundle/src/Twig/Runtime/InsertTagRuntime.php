<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Twig\Extension\RuntimeExtensionInterface;

final class InsertTagRuntime implements RuntimeExtensionInterface
{
    private ContaoFramework $framework;

    /**
     * @internal
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Resolves an insert tag.
     */
    public function replace(string $insertTag): string
    {
        $this->framework->initialize();

        /** @var Controller $controller */
        $controller = $this->framework->getAdapter(Controller::class);

        return $controller->replaceInsertTags('{{'.$insertTag.'}}', false);
    }
}
