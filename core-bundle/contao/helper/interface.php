<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\EditableDataContainerInterface;
use Contao\ListableDataContainerInterface;
use Contao\MaintenanceModuleInterface;
use Contao\UploadableWidgetInterface;

// Register aliases in the global namespace for backwards compatibility
class_exists(ListableDataContainerInterface::class);
class_exists(EditableDataContainerInterface::class);
class_exists(MaintenanceModuleInterface::class);
class_exists(UploadableWidgetInterface::class);

// Let composer find the deprecated interfaces for autoload backwards compatibility
if (!interface_exists('listable', false))
{
	interface listable
	{
	}

	interface editable
	{
	}

	interface executable
	{
	}

	interface uploadable
	{
	}
}
