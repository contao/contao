<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// This file is not used in Contao. Its only purpose is to make PHP IDEs like
// Eclipse, Zend Studio or PHPStorm realize the class origins, since the dynamic
// class aliasing we are using is a bit too complex for them to understand.
namespace  {
	class Newsletter extends \Contao\Newsletter {}
	class NewsletterChannelModel extends \Contao\NewsletterChannelModel {}
	class NewsletterModel extends \Contao\NewsletterModel {}
	class NewsletterRecipientsModel extends \Contao\NewsletterRecipientsModel {}
	class ModuleNewsletterList extends \Contao\ModuleNewsletterList {}
	class ModuleNewsletterReader extends \Contao\ModuleNewsletterReader {}
	class ModuleSubscribe extends \Contao\ModuleSubscribe {}
	class ModuleUnsubscribe extends \Contao\ModuleUnsubscribe {}
}
