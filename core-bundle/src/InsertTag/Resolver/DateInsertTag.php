<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Resolver;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\DefaultParametersInterface;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\Date;

#[AsInsertTag('date', asFragment: true)]
class DateInsertTag implements InsertTagResolverNestedResolvedInterface, DefaultParametersInterface
{
    private const MAPPER = [
        'd' => ['d', 'j', 'l', 'D'],
        'm' => ['m', 'n', 'F', 'M'],
        'Y' => ['Y', 'y'],
    ];

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $this->framework->initialize();

        $date = $this->framework->getAdapter(Date::class);
        $format = $insertTag->getParameters()->get(0); // Always here, ensured by defaults
        $result = new InsertTagResult($date->parse($format), OutputType::text);

        // Add caching headers for supported formats
        $result = $result->withExpiresAt($this->getExpireAtFromFormat($format));

        if ($result->getExpiresAt() && ($rootId = $GLOBALS['objPage']->rootId ?? null)) {
            $result = $result->withCacheTags(["contao.db.tl_page.$rootId"]);
        }

        return $result;
    }

    private function getExpireAtFromFormat(string $format): \DateTimeImmutable|null
    {
        preg_match_all('/[A-Za-z]/', $format, $matches);

        $usedFormatChars = array_unique($matches[0]);

        // Match textual or leading zero representations
        $mapped = [];

        foreach ($usedFormatChars as $usedFormatChar) {
            $hasOneMatch = false;

            foreach (self::MAPPER as $parent => $chars) {
                if (\in_array($usedFormatChar, $chars, true)) {
                    $mapped[] = $parent;
                    $hasOneMatch = true;
                }
            }

            if (!$hasOneMatch) {
                return null;
            }
        }

        if (\in_array('d', $mapped, true)) {
            return new \DateTimeImmutable('today 23:59:59');
        }

        if (\in_array('m', $mapped, true)) {
            return new \DateTimeImmutable('last day of this month 23:59:59');
        }

        if (\in_array('Y', $mapped, true)) {
            return new \DateTimeImmutable('last day of December this year 23:59:59');
        }

        return null;
    }

    public function applyDefaults(InsertTag $tag): InsertTag
    {
        // Format is set, all good
        if ($tag->getParameters()->get(0)) {
            return $tag;
        }

        $parameters = $tag->getParameters()->all();
        $parameters[0] = $GLOBALS['objPage']->dateFormat ?? $GLOBALS['TL_CONFIG']['dateFormat'] ?? '';

        return $tag->withParameters(new ResolvedParameters($parameters));
    }
}
