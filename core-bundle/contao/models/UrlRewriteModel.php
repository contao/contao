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
 * Reads and writes url rewrite rules.
 *
 * @property integer           $id
 * @property integer           $tstamp
 * @property string            $name
 * @property string            $type
 * @property int               $priority
 * @property string            $comment
 * @property bool              $disable
 * @property string            $requestHost
 * @property string            $requestPath
 * @property array|string|null $requestRequirements
 * @property string            $requestCondition
 * @property int               $responseCode
 * @property string            $responseUri
 */
class UrlRewriteModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_url_rewrite';
}
