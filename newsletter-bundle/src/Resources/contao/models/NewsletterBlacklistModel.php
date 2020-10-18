<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 *             Use class NewsletterDenyListModel instead.
 */
class NewsletterBlacklistModel extends NewsletterDenyListModel
{
	public function __construct($objResult = null)
	{
		trigger_deprecation('contao/newsletter-bundle', '4.10', 'Using the "Contao\NewsletterBlacklistModel" class has been deprecated and will no longer work in Contao 5.0. Use the "Contao\NewsletterDenyListModel" class instead.');

		parent::__construct($objResult);
	}
}

class_alias(NewsletterBlacklistModel::class, 'NewsletterBlacklistModel');
