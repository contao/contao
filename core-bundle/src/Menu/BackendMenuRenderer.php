<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Renderer\RendererInterface;
use Twig\Environment;

class BackendMenuRenderer implements RendererInterface
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @internal Do not inherit from this class; decorate the "contao.menu.backend_menu_renderer" service instead
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function render(ItemInterface $tree, array $options = []): string
    {
        return $this->twig->render('@ContaoCore/Backend/be_menu.html.twig', ['tree' => $tree]);
    }
}
