<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Compat;

/**
 * Classes implementing this interface will not be wrapped into proxy objects
 * by the @see \Contao\CoreBundle\Twig\Compat\ProxyFactory when creating a
 * Twig template context from within Contao's template engine.
 *
 * IMPORTANT: If you are tagging your class with this interface, it is your
 * responsibility to sanitize all HTML that the class or any of the classes
 * accessible references outputs!
 */
interface SafeHTMLValueHolderInterface
{
}
