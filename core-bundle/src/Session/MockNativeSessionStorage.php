<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Session;

use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Mocks the native $_SESSION for unit tests.
 */
class MockNativeSessionStorage extends MockArraySessionStorage
{
    public function start(): bool
    {
        parent::start();

        $_SESSION = $this->data;

        return true;
    }
}
