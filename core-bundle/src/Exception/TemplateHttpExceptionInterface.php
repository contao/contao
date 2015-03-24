<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Implement this interface in your exceptions to let Contao automatically render the response via a template.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
interface TemplateHttpExceptionInterface extends HttpExceptionInterface
{
    /**
     * Return the default template name
     *
     * @return string
     */
    public function getDefaultTemplate();
}
