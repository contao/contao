<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\DataContainer\Rewrite;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\StringUtil;

#[AsCallback('tl_url_rewrite', 'fields.requestRequirements.save')]
class RequestRequirementsValidationListener
{
    public function __invoke($value)
    {
        foreach (StringUtil::deserialize($value, true) as $regex) {
            try {
                if (false === preg_match('('.$regex['value'].')', '')) {
                    throw new \RuntimeException();
                }
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(sprintf($GLOBALS['TL_LANG']['tl_url_rewrite']['requestRequirements']['invalid'], $regex['key']), 0, $e);
            }
        }

        return $value;
    }
}
