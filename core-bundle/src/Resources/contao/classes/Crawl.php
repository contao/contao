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
use Contao\CoreBundle\Search\EscargotFactory;
use Nyholm\Psr7\Uri;
use Symfony\Component\HttpFoundation\JsonResponse;
use Terminal42\Escargot\Exception\InvalidJobIdException;

/**
 * Maintenance module "crawl".
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class Crawl extends Backend implements \executable
{
	/**
	 * Return true if the module is active
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return Input::get('act') == 'crawl';
	}

	/**
	 * Generate the module
	 *
	 * @return string
	 */
	public function run()
	{
		$template = new BackendTemplate('be_crawl');
		$template->action = ampersand(Environment::get('request'));
		$template->isActive = $this->isActive();


		/** @var EscargotFactory $factory */
		$factory = System::getContainer()->get('contao.search.escargot_factory');
		$template->subscriberNames = $factory->getSubscriberNames();

		if (!$this->isActive()) {
			return $template->parse();
		}

		$selectedSubscribers = (array) \Input::get('crawl_subscriber_names');
		$jobId = \Input::get('jobId');

		if (!$selectedSubscribers || !($subscribers = $factory->getSubscribers($selectedSubscribers))) {
			$template->error = 'You have to select at least one subscriber!';
			return $template->parse();
		}

		$template->isRunning = true;
		$template->subscribers = $subscribers;

		$queue = $factory->createLazyQueue();

		if (!$jobId) {
			$baseUris = $factory->getSearchUriCollection();
			//$baseUris->add(new Uri('https://www.terminal42.ch')); // TODO: debug
			$baseUris->add(new Uri('https:/contao.org')); // TODO: debug
			$escargot = $factory->create($baseUris, $queue, $selectedSubscribers);
			Controller::redirect(\Controller::addToUrl('&jobId=' . $escargot->getJobId()));
		} else {
			try {
				$escargot = $factory->createFromJobId($jobId, $queue, $selectedSubscribers);
			} catch (InvalidJobIdException $e) {
				Controller::redirect(str_replace('&jobId='. $jobId, '', Environment::get('request')));
			}
		}

		$escargot->setConcurrency(10); // TODO: Configurable
		$escargot->setMaxRequests(rand(3, 8)); // TODO: Configurable

		if (Environment::get('isAjaxRequest')) {
			// Start crawling
			$escargot->crawl();

			// Commit the result on the lazy queue
			$queue->commit($jobId);

			// Return the results
			$pending = $queue->countPending($jobId);
			$all = $queue->countAll($jobId);
			$finished = 0 === $pending;
			$results = [];

			if ($finished) {
				foreach ($factory->getSubscribers($selectedSubscribers) as $subscriber) {
					$results[$subscriber->getName()] = $subscriber->getResultAsHtml($escargot, $jobId);
				}

				$queue->deleteJobId($jobId);
			}

			$response = new JsonResponse([
				'pending' => $pending,
				'total' => $all,
				'finished' => $finished,
				'results' => $results,
			]);

			throw new ResponseException($response);
		}

		return $template->parse();
	}
}
