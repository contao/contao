<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\BackendDashboard;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Controller\BackendDashboard\WelcomeScreenController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Message;
use Contao\System;
use Symfony\Contracts\Translation\TranslatorInterface;

class WelcomeScreenControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    public function testPrintsWelcomeScreen(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->exactly(3))
            ->method('createInstance')
            ->withConsecutive([Message::class], [BackendUser::class], [Config::class])
            ->willReturnOnConsecutiveCalls(
                new Message(),
                $this->createMock(BackendUser::class),
                $this->createMock(Config::class)
            )
        ;

        $translator = $this->createMock(TranslatorInterface::class);

        $controller = new WelcomeScreenController($framework, $translator);

        $controller();
    }
}
