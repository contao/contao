<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

final class MaximumStreamSizeFilter extends \php_user_filter
{
    public const NAME = 'contao.maximum_stream_size';

    private MaximumStreamSizeFilterState $state;

    public function onCreate(): bool
    {
        if (!$this->params instanceof MaximumStreamSizeFilterState) {
            return false;
        }

        $this->state = $this->params;

        return true;
    }

    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;

            if (!$this->state->consume($bucket->datalen)) {
                return PSFS_ERR_FATAL;
            }

            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
