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

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\ModuleModel;
use Twig\Extension\RuntimeExtensionInterface;

final class FragmentRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private ContaoFramework $framework)
    {
    }

    public function renderModule(string $type, array $data = []): string
    {
        $data['type'] = $type;
        $model = new ModuleModel();
        $model->setRow($data);
        $model->preventSaving(false);

        return $this->framework->getAdapter(Controller::class)->getFrontendModule($model);
    }

    public function renderContent(string $type, array $data = []): string
    {
        $data['type'] = $type;
        $model = new ContentModel();
        $model->setRow($data);
        $model->preventSaving(false);

        return $this->framework->getAdapter(Controller::class)->getContentElement($model);
    }
}
