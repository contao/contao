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

interface PickerBuilderInterface
{
    /**
     * Returns a picker or null if the context is not supported.
     *
     * @return PickerInterface|null
     */
    public function create(PickerConfig $config)/*: ?PickerInterface*/;

    /**
     * Returns a picker object from encoded URL data.
     *
     * @param string $data
     *
     * @return PickerInterface|null
     */
    public function createFromData(/*string */$data)/*: ?PickerInterface*/;

    /**
     * Returns whether the given context is supported.
     *
     * @param string $context
     *
     * @return bool
     */
    public function supportsContext(/*string */$context, array $allowed = null)/*: bool*/;

    /**
     * Returns the picker URL for the given context and configuration.
     *
     * @param string $context
     * @param string $value
     *
     * @return string
     */
    public function getUrl(/*string */$context, array $extras = [], /*string */$value = '')/*: string*/;
}
