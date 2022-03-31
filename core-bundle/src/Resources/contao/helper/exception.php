<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Register alias in the global namespace for backwards compatibility
class_exists(\Contao\UnusedArgumentsException::class);

// Let composer find the deprecated class for autoload backwards compatibility
if (!class_exists('UnusedArgumentsException', false))
{
	class UnusedArgumentsException extends Exception
	{
	}
}

/**
 * Class UnresolvableDependenciesException
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 */
class UnresolvableDependenciesException extends Exception
{
}
