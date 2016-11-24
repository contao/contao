<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Bundle\Parser;

/**
 * Delegates parsing to child parsers.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DelegatingParser implements ParserInterface
{
    /**
     * @var ParserInterface[]
     */
    private $parsers = [];

    /**
     * Adds a parser to the chain.
     *
     * @param ParserInterface $parser
     */
    public function addParser(ParserInterface $parser)
    {
        $this->parsers[] = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($resource, $type = null)
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($resource, $type)) {
                return $parser->parse($resource, $type);
            }
        }

        throw new \InvalidArgumentException(sprintf('Cannot parse resources "%s" (type: %s)', $resource, $type));
    }

    /**
     * @inheritdoc
     */
    public function supports($resource, $type = null)
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($resource, $type)) {
                return true;
            }
        }

        return false;
    }
}
