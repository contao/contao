<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\Filesystem\LimitStreamSizeFilter;

// Register the size-limiting VFS write filter during Composer initialization. PHP cannot unregister user filters, so
// registering it on demand would permanently mutate the process-wide filter registry.
if (!\in_array(LimitStreamSizeFilter::NAME, stream_get_filters(), true)) {
    stream_filter_register(LimitStreamSizeFilter::NAME, LimitStreamSizeFilter::class);
}
