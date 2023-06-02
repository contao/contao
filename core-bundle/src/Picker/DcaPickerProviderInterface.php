<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

/**
 * A DcaPickerProvider is able to handle DC_Table or DC_Folder. The interface is optional,
 * because not every picker is based on a regular Contao DataContainer object. If you e.g.
 * implement a Dropbox file picker, a DC is not used.
 */
interface DcaPickerProviderInterface extends PickerProviderInterface
{
    /**
     * Returns the DCA table for this provider.
     */
    public function getDcaTable(PickerConfig|null $config = null): string;

    /**
     * Returns the attributes for the DataContainer.
     *
     * @return array<string, mixed>
     */
    public function getDcaAttributes(PickerConfig $config): array;

    /**
     * Converts the DCA value for the picker selection.
     */
    public function convertDcaValue(PickerConfig $config, mixed $value): int|string;
}
