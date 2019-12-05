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
use Contao\CoreBundle\Search\Escargot\Factory;
use Contao\CoreBundle\Search\Escargot\Subscriber\SubscriberResult;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Terminal42\Escargot\Exception\InvalidJobIdException;

/**
 * Maintenance module "crawl".
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class Crawl extends Backend implements \executable
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
		return Input::get('act') == 'crawl' && $this->valid;
	}

	/**
	 * Generate the module
	 *
	 * @return string
	 */
	public function run()
	{
		// TODO: bring back FE user auth

		$template = new BackendTemplate('be_crawl');
		$template->action = ampersand(Environment::get('request'));
		$template->isActive = $this->isActive();

		/** @var Factory $factory */
		$factory = System::getContainer()->get('contao.search.escargot_factory');

		$subscriberNames = $factory->getSubscriberNames();

		$subscribersWidget = $this->generateSubscribersWidget($subscriberNames);
		$concurrencyWidget = $this->generateConcurrencyWidget();
		$maxRequestsWidget = $this->generateMaxRequestsWidget();

		$template->subscribersWidget = $subscribersWidget;
		$template->concurrencyWidget = $concurrencyWidget;
		$template->maxRequestsWidget = $maxRequestsWidget;

		if (!$this->isActive())
		{
			return $template->parse();
		}

		$activeSubscribers = $subscribersWidget->value;
		$template->isRunning = true;
		$template->activeSubscribers = $factory->getSubscribers($activeSubscribers);

		$jobId = \Input::get('jobId');
		$queue = $factory->createLazyQueue();
		$debugLogPath = sys_get_temp_dir() . '/contao-crawl/' . $jobId . '.log';
		$resultCache = sys_get_temp_dir() . '/contao-crawl/' . $jobId . '.result-cache';

		if ($downloadLog = \Input::get('downloadLog'))
		{
			if ('debug' === $downloadLog)
			{
				$filePath = $debugLogPath;
				$fileName = 'crawl_debug.log';
			}
			else
			{
				$filePath = $this->getSubscriberLogFilePath($downloadLog, $jobId);
				$fileName = 'crawl_' . $downloadLog . '.log';
			}

			$response = new BinaryFileResponse($filePath);
			$response->setPrivate();
			$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);
			throw new ResponseException($response);
		}

		if (!$jobId)
		{
			$baseUris = $factory->getSearchUriCollection();
			$escargot = $factory->create($baseUris, $queue, $activeSubscribers);
			Controller::redirect(\Controller::addToUrl('&jobId=' . $escargot->getJobId()));
		}

		try
		{
			$escargot = $factory->createFromJobId($jobId, $queue, $activeSubscribers);
		}
		catch (InvalidJobIdException $e)
		{
			Controller::redirect(str_replace('&jobId=' . $jobId, '', Environment::get('request')));
		}

		$escargot = $escargot->withConcurrency((int) $concurrencyWidget->value);
		$escargot = $escargot->withMaxRequests((int) $maxRequestsWidget->value);

		try
		{
			$escargot = $factory->createFromJobId($jobId, $queue, $activeSubscribers);
		}
		catch (InvalidJobIdException $e)
		{
			Controller::redirect(str_replace('&jobId=' . $jobId, '', Environment::get('request')));
		}

		$logger = $this->createLogger($factory, $activeSubscribers, $jobId, $debugLogPath);

		$escargot = $escargot->withConcurrency((int) $concurrencyWidget->value);
		$escargot = $escargot->withMaxRequests((int) $maxRequestsWidget->value);
		$escargot = $escargot->withLogger($logger);

		if (Environment::get('isAjaxRequest'))
		{
			// Start crawling
			$escargot->crawl();

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

				if (isset($existingResults[$subscriber->getName()]))
				{
					$previousResult = SubscriberResult::fromArray($existingResults[$subscriber->getName()]);
				}
				$results[$subscriber->getName()] = $subscriber->getResult($previousResult)->toArray();
				$results[$subscriber->getName()]['hasLog'] = file_exists($this->getSubscriberLogFilePath($subscriber->getName(), $jobId));
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

		$template->debugLogHref = \Controller::addToUrl('&jobId=' . $escargot->getJobId() . '&downloadLog=debug');

		$subscriberLogHrefs = array();

		foreach ($factory->getSubscribers($activeSubscribers) as $subscriber)
		{
			$subscriberLogHrefs[$subscriber->getName()] = \Controller::addToUrl('&jobId=' . $escargot->getJobId() . '&downloadLog=' . $subscriber->getName());
		}

		$template->subscriberLogHrefs = $subscriberLogHrefs;

		return $template->parse();
	}

	/**
	 * Creates a logger that logs everything on debug level in a general debug
	 * log file and everything above info level into a subscriber specific
	 * log file.
	 */
	private function createLogger(Factory $factory, array $activeSubscribers, string $jobId, string $debugLogPath): LoggerInterface
	{
		$handlers = array();

		// Create the general debug handler
		$debugHandler = new StreamHandler($debugLogPath, Logger::DEBUG);
		$debugHandler->setFormatter(new LineFormatter("[%context.source%] %message%\n"));
		$handlers[] = $debugHandler;

		// Create the subscriber specific info handlers
		foreach ($factory->getSubscribers($activeSubscribers) as $subscriber)
		{
			$subscriberHandler = new StreamHandler($this->getSubscriberLogFilePath($subscriber->getName(), $jobId), Logger::INFO);
			$subscriberHandler->setFormatter(new LineFormatter("%message%\n"));
			$handlers[] = $subscriberHandler;
		}

		$groupHandler = new GroupHandler($handlers);

		$logger = new Logger('crawl-logger');
		$logger->pushHandler($groupHandler);

		return $logger;
	}

	private function getSubscriberLogFilePath(string $subscriberName, string $jobId): string
	{
		return sys_get_temp_dir() . '/contao-crawl/' . $jobId . '_' . $subscriberName . '.log';
	}

	private function generateSubscribersWidget(array $subscriberNames): Widget
	{
		$name = 'crawl_subscriber_names';
		$widget = new CheckBox();
		$widget->id = $name;
		$widget->name = $name;
		$widget->label = $GLOBALS['TL_LANG']['tl_maintenance']['crawl']['subscribersLabel'][0];
		$widget->mandatory = true;
		$widget->multiple = true;
		$widget->setInputCallback($this->getInputCallback($name));

		$options = array();

		foreach ($subscriberNames as $subscriberName)
		{
			$options[] = array(
				'value' => $subscriberName,
				'label' => $GLOBALS['TL_LANG']['tl_maintenance']['crawl']['subscriberNames'][$subscriberName],
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

	private function generateConcurrencyWidget(): Widget
	{
		$name = 'crawl_concurrency';
		$widget = new TextField();
		$widget->id = $name;
		$widget->name = $name;
		$widget->label = $GLOBALS['TL_LANG']['tl_maintenance']['crawl']['concurrencyLabel'][0];
		$widget->rgxp = 'digit';
		$widget->value = 10;
		$widget->setInputCallback($this->getInputCallback($name));

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

	private function generateMaxRequestsWidget(): Widget
	{
		$name = 'crawl_max_requests';
		$widget = new TextField();
		$widget->id = $name;
		$widget->name = $name;
		$widget->label = $GLOBALS['TL_LANG']['tl_maintenance']['crawl']['maxRequestsLabel'][0];
		$widget->rgxp = 'digit';
		$widget->value = 20;
		$widget->setInputCallback($this->getInputCallback($name));

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
		return static function () use ($name)
		{
			return \Input::get($name);
		};
	}
}
