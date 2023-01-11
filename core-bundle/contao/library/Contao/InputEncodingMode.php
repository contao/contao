<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

enum InputEncodingMode
{
	case encodeAll;
	case encodeLessThanSign;
	case sanitizeHtml;
	case encodeNone;
}
