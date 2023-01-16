<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\DataContainer\Rewrite;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

#[AsCallback('tl_url_rewrite', 'fields.examples.input_field')]
class RewriteExamplesListener
{
    public function __invoke(): string
    {
        $buffer = '';

        foreach ($GLOBALS['TL_LANG']['tl_url_rewrite']['examples'] as $i => $example) {
            $buffer .= sprintf(
                '<h3>%s. %s</h3><pre style="margin-top:.5rem;padding:1rem;background:#f6f6f8;font-size:.75rem;">%s</pre>',
                $i + 1,
                $example[0],
                $example[1]
            );
        }

        return sprintf('<div class="widget long">%s</div>', $buffer);
    }
}
