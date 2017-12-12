<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Menu\BackendMenu;

use Contao\CoreBundle\Menu\BackendMenuRenderer;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class BackendMenuRendererTest extends TestCase
{
    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $templating;

    /**
     * @var BackendMenuRenderer
     */
    private $renderer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->templating = $this->createMock(Environment::class);
        $this->renderer = new BackendMenuRenderer($this->templating);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Menu\BackendMenuRenderer', $this->renderer);
    }

    public function testRendersTheBackendMenuTemplate(): void
    {
        $tree = $this->createMock(ItemInterface::class);

        $this->templating
            ->expects($this->once())
            ->method('render')
            ->with('ContaoCoreBundle:Backend:be_menu.html.twig', ['tree' => $tree])
            ->willReturn('')
        ;

        $this->renderer->render($tree);
    }
}
