<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\DataContainer\Rewrite;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

#[AsCallback('tl_url_rewrite', 'list.label.label')]
class RewriteLabelListener
{
    public function __invoke(array $row): string
    {
        if (410 === (int) $row['responseCode']) {
            $response = $row['responseCode'];
        } else {
            $response = sprintf('%s, %s', $row['responseUri'], $row['responseCode']);
        }

        return sprintf(
            '%s <span style="padding-left:3px;color:#b3b3b3;word-break:break-all;">[%s &rarr; %s (%s: %s)]</span>',
            $row['name'],
            $row['requestPath'],
            $response,
            $GLOBALS['TL_LANG']['tl_url_rewrite']['priority'][0],
            $row['priority']
        );
    }
}
