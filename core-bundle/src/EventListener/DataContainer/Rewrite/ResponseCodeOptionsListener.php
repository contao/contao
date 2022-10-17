<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\DataContainer\Rewrite;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Symfony\Component\HttpFoundation\Response;

#[AsCallback('tl_url_rewrite', 'fields.responseCode.options')]
class ResponseCodeOptionsListener
{
    public function __invoke(): array
    {
        $options = [];

        foreach ([301, 302, 303, 307, 410] as $code) {
            $options[$code] = $code.' '.Response::$statusTexts[$code];
        }

        return $options;
    }
}
