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
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\ModuleModel;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Twig\Extension\RuntimeExtensionInterface;

final class HttpKernelRuntime implements RuntimeExtensionInterface
{
    private FragmentRegistryInterface $fragmentRegistry;
    private ContaoFramework $framework;

    /**
     * @internal
     */
    public function __construct(FragmentRegistryInterface $fragmentRegistry, ContaoFramework $framework)
    {
        $this->fragmentRegistry = $fragmentRegistry;
        $this->framework = $framework;
    }

    public function resolveContentElement(int $id): ControllerReference
    {
        $this->framework->initialize();

        /** @var ContentModel|null $modelAdapter */
        $modelAdapter = $this->framework->getAdapter(ContentModel::class);

        if (null === ($model = $modelAdapter->findByPk($id))) {
            throw new \InvalidArgumentException("A content element with the ID '$id' could not be found.");
        }

        if (null === ($config = $this->fragmentRegistry->get("contao.content_element.$model->type"))) {
            throw new \InvalidArgumentException("A content element of type '$model->type' could not be found in the fragment registry.");
        }

        return new ControllerReference($config->getController(), ['model' => $model, 'section' => '']);
    }

    public function resolveModule(int $id): ControllerReference
    {
        $this->framework->initialize();

        /** @var ModuleModel|null $modelAdapter */
        $modelAdapter = $this->framework->getAdapter(ModuleModel::class);

        if (null === ($model = $modelAdapter->findByPk($id))) {
            throw new \InvalidArgumentException("A module with the ID '$id' could not be found.");
        }

        if (null === ($config = $this->fragmentRegistry->get("contao.module.$model->type"))) {
            throw new \InvalidArgumentException("A module of type '$model->type' could not be found in the fragment registry.");
        }

        return new ControllerReference($config->getController(), ['model' => $model, 'section' => '']);
    }
}
