<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Exception;

use Contao\Model;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class ContentRouteNotFoundException extends RouteNotFoundException
{
    private $content;

    public function __construct($content, $code = 0, \Throwable $previous = null)
    {
        parent::__construct('No route found for '.$this->getRouteDebugMessage($content), $code, $previous);

        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    private function getRouteDebugMessage($content): string
    {
        if (is_scalar($content)) {
            return $content;
        }

        if (\is_array($content)) {
            return serialize($content);
        }

        if ($content instanceof Route) {
            return 'path '.$content->getPath();
        }

        if ($content instanceof Model) {
            return $content::getTable().'.'.$content->{$content::getPk()};
        }

        if (\is_object($content)) {
            return \get_class($content);
        }

        return 'unknown route';
    }
}
