<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

class UnusedArgumentsException extends \Exception
{
}

class_alias(UnusedArgumentsException::class, 'UnusedArgumentsException');
