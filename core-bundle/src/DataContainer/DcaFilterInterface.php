<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataContainer;

/**
 * DCA filter interface.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
interface DcaFilterInterface
{
    /**
     * Returns the filter array.
     *
     * @return array
     *
     * @see DataContainer::setDcaFilter()
     * @see DC_Folder::setDcaFilter()
     */
    public function getDcaFilter();
}
