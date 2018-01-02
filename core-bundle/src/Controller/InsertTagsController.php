<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\InsertTags;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal Do not use this controller in your code
 *
 * It is supposed to be used within ESI requests that are protected by the
 * Symfony fragment URI signer. If you use it directly, make sure to add a
 * permission check, because insert tags can contain arbitrary data!
 */
class InsertTagsController extends Controller
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @param $framework
     */
    public function __construct($framework)
    {
        $this->framework = $framework;
    }

    /**
     * Renders an insert tag.
     *
     * @param string $insertTag
     *
     * @return Response
     */
    public function renderAction(string $insertTag): Response
    {
        $this->framework->initialize();

        /** @var InsertTags $it */
        $it = $this->framework->createInstance(InsertTags::class);

        // Never cache these responses
        return (new Response($it->replace($insertTag, false)))->setPrivate();
    }
}
