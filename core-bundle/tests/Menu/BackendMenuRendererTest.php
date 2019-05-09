<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Menu;

use Contao\CoreBundle\Menu\BackendMenuRenderer;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class BackendMenuRendererTest extends TestCase
{
    /**
     * @var Environment&MockObject
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

    public function testRendersTheBackendMenuTemplate(): void
    {
        $tree = $this->createMock(ItemInterface::class);

        $this->templating
            ->expects($this->once())
            ->method('render')
            ->with('@ContaoCore/Backend/be_menu.html.twig', ['tree' => $tree])
            ->willReturn('')
        ;

        $this->renderer->render($tree);
    }
}
