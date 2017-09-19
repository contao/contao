<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Picker;

/**
 * Interface for DCA picker providers.
 *
 * A DcaPickerProvider is able to handle DC_Table or DC_Folder. The interface is optional, because not every picker is
 * based on a regular Contao DataContainer object. If you e.g. implement a Dropbox file picker, a DC is not used.
 */
interface DcaPickerProviderInterface extends PickerProviderInterface
{
    /**
     * Returns the DCA table for this provider.
     *
     * @return string
     */
    public function getDcaTable();

    /**
     * Returns the attributes for the DataContainer.
     *
     * @param PickerConfig $config
     *
     * @return array
     */
    public function getDcaAttributes(PickerConfig $config);

    /**
     * Converts the DCA value for the picker selection.
     *
     * @param PickerConfig $config
     * @param mixed        $value
     *
     * @return mixed
     */
    public function convertDcaValue(PickerConfig $config, $value);
}
