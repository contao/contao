<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\DataContainer\Rewrite;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Input;

#[AsCallback('tl_url_rewrite', 'fields.name.save')]
class NameValidationListener
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke($value, DataContainer $dataContainer)
    {
        if ('' === $value) {
            $inputAdapter = $this->framework->getAdapter(Input::class);
            $value = $inputAdapter->post('requestPath') ?: $dataContainer->activeRecord->requestPath;
        }

        if ('' === $value) {
            throw new \InvalidArgumentException(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $dataContainer->field));
        }

        return $value;
    }
}
