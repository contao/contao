<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Crawl\Escargot\Subscriber\SubscriberResult;
use Contao\CoreBundle\Crawl\Monolog\CrawlCsvLogHandler;
use Contao\CoreBundle\Exception\ResponseException;
use Monolog\Handler\GroupHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Terminal42\Escargot\Exception\InvalidJobIdException;

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
	 * @var string
	 */
	private $logDir;

	/**
	 * Return true if the module is active
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return Input::get('act') == 'crawl' && $this->valid;
	}

	/**
	 * Generate the module
	 *
	 * @return string
	 */
	public function run()
	{
		if (!System::getContainer()->has('contao.crawl.escargot.factory'))
		{
			return '';
		}

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
		$template->isActive = $this->isActive();
		$template->subscribersWidget = $subscribersWidget;
		$template->maxDepthWidget = $maxDepthWidget;
		$template->memberWidget = $memberWidget;

		if (!$this->isActive())
		{
			return $template->parse();
		}

		$activeSubscribers = $subscribersWidget->value;

		$template->isRunning = true;
		$template->activeSubscribers = $factory->getSubscribers($activeSubscribers);

		$jobId = Input::get('jobId');
		$queue = $factory->createLazyQueue();

		$debugLogPath = $this->getLogDir() . '/' . $jobId . '_log.csv';
		$resultCache = $this->getLogDir() . '/' . $jobId . '.result-cache';

		if ($downloadLog = Input::get('downloadLog'))
		{
			if ('debug' === $downloadLog)
			{
				$filePath = $debugLogPath;
				$fileName = 'crawl_debug_log.csv';
			}
			else
			{
				$filePath = $this->getSubscriberLogFilePath($downloadLog, $jobId);
				$fileName = 'crawl_' . $downloadLog . '_log.csv';
			}

			$response = new BinaryFileResponse($filePath);
			$response->setPrivate();
			$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

			throw new ResponseException($response);
		}

		$objAuthenticator = System::getContainer()->get('contao.security.frontend_preview_authenticator');

		if ($memberWidget && $memberWidget->value)
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
				$session = System::getContainer()->get('request_stack')->getSession();
				$clientOptions = array('headers' => array('Cookie' => sprintf('%s=%s', $session->getName(), $session->getId())));

				// Closing the session is necessary here as otherwise we run into our own session lock
				// TODO: we need a way to authenticate with a token instead of our own cookie
				$session->save();
			}
		}
		else
		{
			$objAuthenticator->removeFrontendAuthentication();
			$clientOptions = array();
		}

		if (!$jobId)
		{
			$baseUris = $factory->getCrawlUriCollection();
			$escargot = $factory->create($baseUris, $queue, $activeSubscribers, $clientOptions);

			Controller::redirect(Controller::addToUrl('&jobId=' . $escargot->getJobId()));
		}

		$escargot = null;

		try
		{
			$escargot = $factory->createFromJobId($jobId, $queue, $activeSubscribers, $clientOptions);
		}
		catch (InvalidJobIdException $e)
		{
			if (file_exists($resultCache))
			{
				$results = json_decode(file_get_contents($resultCache), true);

				if (Environment::get('isAjaxRequest'))
				{
					$response = new JsonResponse(array(
						'pending' => 0,
						'total' => 0,
						'finished' => true,
						'results' => $results,
						'hasDebugLog' => file_exists($debugLogPath),
					));

					throw new ResponseException($response);
				}

				$subscriberLogHrefs = array();

				foreach (array_keys($results) as $name)
				{
					$subscriberLogHrefs[$name] = Controller::addToUrl('&jobId=' . $jobId . '&downloadLog=' . $name);
				}

				$template->subscriberLogHrefs = $subscriberLogHrefs;
				$template->debugLogHref = Controller::addToUrl('&jobId=' . $jobId . '&downloadLog=debug');

				return $template->parse();
			}

			Controller::redirect(str_replace('&jobId=' . $jobId, '', Environment::get('requestUri')));
		}

		// Configure with sane defaults for the back end (maybe we should make this configurable one day)
		$escargot = $escargot
			->withConcurrency(System::getContainer()->getParameter('contao.crawl.concurrency'))
			->withMaxDepth($maxDepthWidget->value)
			->withMaxRequests(System::getContainer()->getParameter('contao.crawl.max_requests'))
			->withLogger($this->createLogger($factory, $activeSubscribers, $jobId, $debugLogPath));

		if (Environment::get('isAjaxRequest'))
		{
			// Start crawling
			if ('true' !== Environment::get('httpOnlyStatusUpdate'))
			{
				$escargot->crawl();
			}

			// Commit the result on the lazy queue
			$queue->commit($jobId);

			// Save the results between requests
			$results = array();
			$existingResults = array();

			if (file_exists($resultCache))
			{
				$existingResults = json_decode(file_get_contents($resultCache), true);
			}

			foreach ($factory->getSubscribers($activeSubscribers) as $subscriber)
			{
				$previousResult = null;
				$name = $subscriber->getName();

				if (isset($existingResults[$name]))
				{
					$previousResult = SubscriberResult::fromArray($existingResults[$name]);
				}

				$results[$name] = $subscriber->getResult($previousResult)->toArray();
				$results[$name]['hasLog'] = file_exists($this->getSubscriberLogFilePath($name, $jobId));
			}

			file_put_contents($resultCache, json_encode($results));

			// Return the results
			$pending = $queue->countPending($jobId);
			$all = $queue->countAll($jobId);
			$finished = 0 === $pending;

			if ($finished)
			{
				$queue->deleteJobId($jobId);
			}

			$response = new JsonResponse(array(
				'pending' => $pending,
				'total' => $all,
				'finished' => $finished,
				'results' => $results,
				'hasDebugLog' => file_exists($debugLogPath),
			));

			throw new ResponseException($response);
		}

		$template->debugLogHref = Controller::addToUrl('&jobId=' . $escargot->getJobId() . '&downloadLog=debug');

		$subscriberLogHrefs = array();

		foreach ($factory->getSubscribers($activeSubscribers) as $subscriber)
		{
			$name = $subscriber->getName();
			$subscriberLogHrefs[$name] = Controller::addToUrl('&jobId=' . $escargot->getJobId() . '&downloadLog=' . $name);
		}

		$template->subscriberLogHrefs = $subscriberLogHrefs;

		return $template->parse();
	}

	/**
	 * Creates a logger that logs everything on debug level in a general debug
	 * log file and everything above info level into a subscriber specific log
	 * file.
	 */
	private function createLogger(Factory $factory, array $activeSubscribers, string $jobId, string $debugLogPath): LoggerInterface
	{
		$handlers = array();

		// Create the general debug handler
		$debugHandler = new CrawlCsvLogHandler($debugLogPath, Logger::DEBUG);
		$handlers[] = $debugHandler;

		// Create the subscriber specific info handlers
		foreach ($factory->getSubscribers($activeSubscribers) as $subscriber)
		{
			$subscriberHandler = new CrawlCsvLogHandler($this->getSubscriberLogFilePath($subscriber->getName(), $jobId), Logger::INFO);
			$subscriberHandler->setFilterSource(\get_class($subscriber));
			$handlers[] = $subscriberHandler;
		}

		$groupHandler = new GroupHandler($handlers);

		$logger = new Logger('crawl-logger');
		$logger->pushHandler($groupHandler);

		return $logger;
	}

	private function getLogDir(): string
	{
		if (null !== $this->logDir)
		{
			return $this->logDir;
		}

		$this->logDir = sprintf('%s/%s/contao-crawl', sys_get_temp_dir(), md5(System::getContainer()->getParameter('kernel.project_dir')));

		if (!is_dir($this->logDir))
		{
			(new Filesystem())->mkdir($this->logDir);
		}

		return $this->logDir;
	}

	private function getSubscriberLogFilePath(string $subscriberName, string $jobId): string
	{
		return $this->getLogDir() . '/' . $jobId . '_' . $subscriberName . '_log.csv';
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
		$widget->setInputCallback($this->getInputCallback($name));

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
		$widget->setInputCallback($this->getInputCallback($name));

		$options = array();
		$default = System::getContainer()->getParameter('contao.crawl.max_depth');

		for ($i = 1; $i <= 20; ++$i) {
			$options[$i] = array(
				'value' => $i,
				'label' => $i,
				'default' => $i === $default,
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
		$widget->setInputCallback($this->getInputCallback($name));

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

	private function getInputCallback(string $name): \Closure
	{
		return static function () use ($name) {
			return Input::get($name);
		};
	}
}
