<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
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
     * @param Environment $twig
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
