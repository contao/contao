<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Messenger\Message\CrawlMessage;
use Symfony\Component\HttpFoundation\JsonResponse;

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
		return (bool) Input::post('trigger_crawl');
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
		$indexProtected = System::getContainer()->getParameter('contao.search.index_protected');
		$user = Input::post('crawl_member');

		if ($indexProtected && Input::post('FORM_SUBMIT') == 'datalist_members')
		{
			$data = $this->getMembersDataList();

			throw new ResponseException(new JsonResponse($data));
		}

		$template = new BackendTemplate('be_crawl');
		$template->message = Message::generateUnwrapped(self::class);
		$template->subscribersWidget = $subscribersWidget;
		$template->maxDepthWidget = $maxDepthWidget;
		$template->isActive = $this->isActive();
		$template->indexProtected = $indexProtected;
		$template->user = $user;

		if ($this->isActive() && $this->valid)
		{
			$headers = array();
			$objAuthenticator = System::getContainer()->get('contao.security.frontend_preview_authenticator');

			if ($indexProtected)
			{
				if (!$objAuthenticator->authenticateFrontendUser($user, false))
				{
					$template->invalidUser = true;
					$objAuthenticator->removeFrontendAuthentication();
					System::getContainer()->get('request_stack')?->getMainRequest()->attributes->set('_contao_widget_error', true);

					return $template->parse();
				}

				// TODO: we need a way to authenticate with a token instead of our own cookie
				$session = System::getContainer()->get('request_stack')->getSession();
				$headers = array('Cookie' => \sprintf('%s=%s', $session->getName(), $session->getId()));
			}
			else
			{
				$objAuthenticator->removeFrontendAuthentication();
			}

			$subscribers = $subscribersWidget->value;
			$maxDepth = $maxDepthWidget->value;

			$jobs = System::getContainer()->get('contao.job.jobs');
			$job = $jobs->createJob('crawl');
			$jobs->dispatchJob(new CrawlMessage($subscribers, $maxDepth, $headers), $job);

			Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['confirmJobAdded'], self::class);

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

	private function getMembersDataList(): array
	{
		$security = System::getContainer()->get('security.helper');
		$connection = System::getContainer()->get('database_connection');

		$andWhereGroups = '';

		if (!$security->isGranted('ROLE_ADMIN')) {
			$amg = StringUtil::deserialize(BackendUser::getInstance()->amg);
			$groups = array_map(static fn ($groupId): string => '%"'.(int) $groupId.'"%', $amg);
			$andWhereGroups = "AND (`groups` LIKE '".implode("' OR `groups` LIKE '", $groups)."')";
		}

		$time = Date::floorToMinute();

		// Get the active front end users
		$query = <<<SQL
            SELECT username
            FROM tl_member
            WHERE
                username LIKE ?
                $andWhereGroups
                AND login = 1
                AND disable = 0
                AND (start = '' OR start <= $time)
                AND (stop = '' OR stop > $time)
            ORDER BY username
            SQL;

		$query = $connection->getDatabasePlatform()->modifyLimitQuery($query, 20);

		return $connection
			->executeQuery($query, [str_replace('%', '', Input::post('value')).'%'])
			->fetchFirstColumn()
		;
	}
}
