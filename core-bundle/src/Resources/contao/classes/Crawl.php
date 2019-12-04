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
use Symfony\Component\HttpFoundation\JsonResponse;
use Contao\CoreBundle\Search\Escargot\Subscriber\SubscriberResult;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

		$template->isRunning = true;
		$template->activeSubscribers = $factory->getSubscribers($subscribersWidget->value);

		$jobId = \Input::get('jobId');
		$queue = $factory->createLazyQueue();

		if (!$jobId)
		{
			$baseUris = $factory->getSearchUriCollection();
			$escargot = $factory->create($baseUris, $queue, $subscribersWidget->value);
			Controller::redirect(\Controller::addToUrl('&jobId=' . $escargot->getJobId()));
		}

		try
		{
			$escargot = $factory->createFromJobId($jobId, $queue, $subscribersWidget->value);
		}
		catch (InvalidJobIdException $e)
		{
			Controller::redirect(str_replace('&jobId='. $jobId, '', Environment::get('request')));
		}

		$escargot = $escargot->withConcurrency((int) $concurrencyWidget->value);
		$escargot = $escargot->withMaxRequests((int) $maxRequestsWidget->value);

		$logPath = sys_get_temp_dir() . '/contao-crawl/' . $jobId . '.log';
		$resultCache = sys_get_temp_dir() . '/contao-crawl/' . $jobId . '.result-cache';

		if (\Input::get('download') === 'log')
		{
			$response = new BinaryFileResponse($logPath);
			$response->setPrivate();
			$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'crawl.log');
			throw new ResponseException($response);
		}

		try
		{
			$escargot = $factory->createFromJobId($jobId, $queue, $subscribersWidget->value);
		}
		catch (InvalidJobIdException $e)
		{
			Controller::redirect(str_replace('&jobId='. $jobId, '', Environment::get('request')));
		}

		$streamHandler = new StreamHandler($logPath);
		$streamHandler->setFormatter(new LineFormatter("[%context.source%] %message%\n"));
		$logger = new Logger('file-logger');
		$logger->pushHandler($streamHandler);

		$escargot = $escargot->withConcurrency((int) $concurrencyWidget->value);
		$escargot = $escargot->withMaxRequests((int) $maxRequestsWidget->value);
		$escargot = $escargot->withLogger($logger);

		if (Environment::get('isAjaxRequest'))
		{
			// Start crawling
			$escargot->crawl();

			// Commit the result on the lazy queue
			$queue->commit($jobId);

			// Write the logs
			$streamHandler->close();

			// Save the results between requests
			$results = [];
			$existingResults = [];
			if (file_exists($resultCache)) {
				$existingResults = json_decode(file_get_contents($resultCache), true);
			}

			foreach ($factory->getSubscribers($subscribersWidget->value) as $subscriber)
			{
				$previousResult = null;
				if (isset($existingResults[$subscriber->getName()])) {
					$previousResult = SubscriberResult::fromArray($existingResults[$subscriber->getName()]);
				}
				$results[$subscriber->getName()] = $subscriber->getResult($previousResult)->toArray();
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

			$response = new JsonResponse([
				'pending' => $pending,
				'total' => $all,
				'finished' => $finished,
				'results' => $results,
				'hasLog' => file_exists($logPath),
			]);

			throw new ResponseException($response);
		}

		$template->logHref = \Controller::addToUrl('&jobId=' . $escargot->getJobId() . '&download=log');

		return $template->parse();
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

		$options = [];

		foreach ($subscriberNames as $subscriberName) {
			$options[] = [
				'value' => $subscriberName,
				'label' => $GLOBALS['TL_LANG']['tl_maintenance']['crawl']['subscriberNames'][$subscriberName],
				'default' => false,
			];
		}

		if (1 === \count($options)) {
			$options[0]['default'] = true;
		}

		$widget->options = $options;

		if ($this->isActive()) {
			$widget->validate();

			if ($widget->hasErrors()) {
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

		if ($this->isActive()) {
			$widget->validate();

			if ($widget->hasErrors()) {
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

		if ($this->isActive()) {
			$widget->validate();

			if ($widget->hasErrors()) {
				$this->valid = false;
			}
		}

		return $widget;
	}

	private function getInputCallback(string $name): \Closure
	{
		return function () use ($name) {
			return \Input::get($name);
		};
	}
}
