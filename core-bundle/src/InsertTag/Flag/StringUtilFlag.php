<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Flag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTagFlag;
use Contao\CoreBundle\InsertTag\InsertTagFlag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\StringUtil;
use Symfony\Component\String\UnicodeString;

class StringUtilFlag
{
    #[AsInsertTagFlag('attr')]
    public function attr(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            StringUtil::specialcharsAttribute($result->getValue()),
            OutputType::html,
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }

    #[AsInsertTagFlag('urlattr')]
    public function urlattr(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            StringUtil::specialcharsUrl($result->getValue()),
            OutputType::html, // Not type URL because HTML entities are encoded here
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }

    #[AsInsertTagFlag('standardize')]
    public function standardize(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            StringUtil::standardize($result->getValue()),
            OutputType::text,
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }

    #[AsInsertTagFlag('ampersand')]
    public function ampersand(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            StringUtil::ampersand($result->getValue()),
            OutputType::text, // We can not safely assume HTML here as the method only encodes ampersands
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }

    #[AsInsertTagFlag('specialchars')]
    public function specialchars(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            StringUtil::specialchars($result->getValue()),
            OutputType::html,
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }

    #[AsInsertTagFlag('encode_email')]
    #[AsInsertTagFlag('encodeemail')]
    public function encodeEmail(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            StringUtil::encodeEmail($result->getValue()),
            OutputType::html === $result->getOutputType() ? OutputType::html : OutputType::text,
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }

    #[AsInsertTagFlag('utf8_strtolower')]
    public function utf8Strtolower(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            mb_strtolower($result->getValue()),
            OutputType::html === $result->getOutputType() ? OutputType::html : OutputType::text,
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }

    #[AsInsertTagFlag('utf8_strtoupper')]
    public function utf8Strtoupper(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            mb_strtoupper($result->getValue()),
            OutputType::html === $result->getOutputType() ? OutputType::html : OutputType::text,
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }

    #[AsInsertTagFlag('utf8_romanize')]
    public function utf8Romanize(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            (new UnicodeString($result->getValue()))->ascii()->toString(),
            OutputType::html === $result->getOutputType() ? OutputType::html : OutputType::text,
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }

    #[AsInsertTagFlag('nl2br')]
    public function nl2Br(InsertTagFlag $flag, InsertTagResult $result): InsertTagResult
    {
        return new InsertTagResult(
            preg_replace('/\r?\n/', '<br>', $result->getValue()),
            OutputType::html === $result->getOutputType() ? OutputType::html : OutputType::text,
            $result->getExpiresAt(),
            $result->getCacheTags(),
        );
    }
}
