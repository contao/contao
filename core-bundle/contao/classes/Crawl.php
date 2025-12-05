<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Messenger\Message\CrawlMessage;

/**
 * Maintenance module "crawl".
 */
class Crawl extends Backend implements MaintenanceModuleInterface
{
	/**
	 * @var bool
	 */
	private $valid = true;

	/**
	 * Return true if the module is active
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return Input::post('trigger_crawl');
	}

	/**
	 * Generate the module
	 *
	 * @return string
	 */
	public function run()
	{
		$factory = System::getContainer()->get('contao.crawl.escargot.factory');
		$subscriberNames = $factory->getSubscriberNames();

		$subscribersWidget = $this->generateSubscribersWidget($subscriberNames);
		$maxDepthWidget = $this->generateMaxDepthWidget();
		$memberWidget = null;

		if (System::getContainer()->getParameter('contao.search.index_protected'))
		{
			$memberWidget = $this->generateMemberWidget();
		}

		$template = new BackendTemplate('be_crawl');
		$template->subscribersWidget = $subscribersWidget;
		$template->maxDepthWidget = $maxDepthWidget;
		$template->memberWidget = $memberWidget;
		$template->isActive = $this->isActive();

		if ($this->isActive() && $this->valid)
		{
			$objAuthenticator = System::getContainer()->get('contao.security.frontend_preview_authenticator');

			if ($memberWidget?->value)
			{
				$objMember = Database::getInstance()->prepare('SELECT username FROM tl_member WHERE id=?')
					->execute((int) $memberWidget->value);

				if (!$objAuthenticator->authenticateFrontendUser($objMember->username, false))
				{
					$objAuthenticator->removeFrontendAuthentication();
					$clientOptions = array();
				}
				else
				{
					// TODO: we need a way to authenticate with a token instead of our own cookie
					$session = System::getContainer()->get('request_stack')->getSession();
					$headers = array('Cookie' => \sprintf('%s=%s', $session->getName(), $session->getId()));
				}
			}
			else
			{
				$objAuthenticator->removeFrontendAuthentication();
				$headers = array();
			}

			$subscribers = $subscribersWidget->value;
			$maxDepth = $maxDepthWidget->value;

			$job = System::getContainer()->get('contao.job.jobs')->createJob('crawl');
			System::getContainer()->get('messenger.bus.default')->dispatch(new CrawlMessage(
				$job->getUuid(),
				$subscribers,
				$maxDepth,
				$headers,
			));

			// TODO: translation
			Message::addConfirmation('Yo! Check the jobs framework!', self::class);

			$this->reload();
		}

		return $template->parse();
	}

	private function generateSubscribersWidget(array $subscriberNames): Widget
	{
		$name = 'crawl_subscriber_names';
		$widget = new CheckBox();
		$widget->id = $name;
		$widget->name = $name;
		$widget->label = $GLOBALS['TL_LANG']['tl_maintenance']['crawlSubscribers'][0];
		$widget->mandatory = true;
		$widget->multiple = true;

		$options = array();

		foreach ($subscriberNames as $subscriberName)
		{
			$options[] = array(
				'value' => $subscriberName,
				'label' => $GLOBALS['TL_LANG']['tl_maintenance']['crawlSubscriberNames'][$subscriberName],
				'default' => false,
			);
		}

		if (1 === \count($options))
		{
			$options[0]['default'] = true;
		}

		$widget->options = $options;

		if ($this->isActive())
		{
			$widget->validate();

			if ($widget->hasErrors())
			{
				$this->valid = false;
			}
		}

		return $widget;
	}

	private function generateMaxDepthWidget(): Widget
	{
		$name = 'crawl_depth';

		$widget = new SelectMenu();
		$widget->id = $name;
		$widget->name = $name;
		$widget->label = $GLOBALS['TL_LANG']['tl_maintenance']['crawlDepth'][0];

		$options = array();

		for ($i = 3; $i <= 10; ++$i)
		{
			$options[$i] = array(
				'value' => $i,
				'label' => $i,
			);
		}

		$widget->options = $options;

		if ($this->isActive())
		{
			$widget->validate();

			if ($widget->hasErrors())
			{
				$this->valid = false;
			}
		}

		return $widget;
	}

	private function generateMemberWidget(): Widget
	{
		$name = 'crawl_member';

		$widget = new SelectMenu();
		$widget->id = $name;
		$widget->name = $name;
		$widget->label = $GLOBALS['TL_LANG']['tl_maintenance']['crawlMember'][0];

		$time = time();
		$options = array(array('value' => '', 'label' => '-', 'default' => true));
		$objMembers = null;

		// Get the active front end users
		if (BackendUser::getInstance()->isAdmin)
		{
			$objMembers = Database::getInstance()->execute("SELECT id, username FROM tl_member WHERE login=1 AND disable=0 AND (start='' OR start<=$time) AND (stop='' OR stop>$time) ORDER BY username");
		}
		else
		{
			$amg = StringUtil::deserialize(BackendUser::getInstance()->amg);

			if (!empty($amg) && \is_array($amg))
			{
				$objMembers = Database::getInstance()->execute("SELECT id, username FROM tl_member WHERE (`groups` LIKE '%\"" . implode('"%\' OR \'%"', array_map('\intval', $amg)) . "\"%') AND login=1 AND disable=0 AND (start='' OR start<=$time) AND (stop='' OR stop>$time) ORDER BY username");
			}
		}

		if ($objMembers !== null)
		{
			while ($objMembers->next())
			{
				$options[] = array(
					'value' => $objMembers->id,
					'label' => $objMembers->username . ' (' . $objMembers->id . ')'
				);
			}
		}

		$widget->options = $options;

		if ($this->isActive())
		{
			$widget->validate();

			if ($widget->hasErrors())
			{
				$this->valid = false;
			}
		}

		return $widget;
	}
}
