<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework\Adapter;

/**
 * This interface does not define any methods as one cannot define __call()
 * in an interface. However, we still provide it so one can type hint against
 * it when using dependency injection. It does not make any difference from
 * the application flow perspective but it's a way to give a hint to any
 * developer that whatever is injected is an adapter for a legacy class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 *
 */
interface AdapterInterface {}
