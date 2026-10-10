<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\User;

use Contao\User;

/**
 * Parent class to check if a user object belongs to Contao.
 */
abstract class ContaoUser extends User
{
}
