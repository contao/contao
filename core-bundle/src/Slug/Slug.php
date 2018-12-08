<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Slug;

use Ausi\SlugGenerator\SlugGenerator;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\PageModel;
use Contao\StringUtil;

class Slug
{
    /**
     * @var SlugGenerator
     */
    private $slugGenerator;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    public function __construct(SlugGenerator $slugGenerator, ContaoFrameworkInterface $framework)
    {
        $this->slugGenerator = $slugGenerator;
        $this->framework = $framework;
    }

    /**
     * @param int|iterable $options        Page id to get the options from or options object {@see SlugGenerator::generate()}
     * @param callable     $duplicateCheck Callback to check if the slug is already in use: function(string $slug): bool
     */
    public function generate(string $text, $options = [], callable $duplicateCheck = null, string $integerPrefix = 'id-'): string
    {
        if (!is_iterable($options)) {
            /** @var $pageAdapter PageModel */
            $pageAdapter = $this->framework->getAdapter(PageModel::class);
            if (($page = $pageAdapter->findWithDetails((int) $options)) !== null) {
                $options = $page->getSlugOptions();
            } else {
                $options = [];
            }
        }

        $text = StringUtil::prepareSlug($text);
        $slug = $this->slugGenerator->generate($text, $options);

        if (preg_match('/^[1-9][0-9]*$/', $slug)) {
            $slug = $integerPrefix.$slug;
        }

        if (null === $duplicateCheck) {
            return $slug;
        }

        $base = $slug;
        for ($count = 2; $duplicateCheck($slug); $count++) {
            $slug = $base.'-'.$count;
        }

        return $slug;
    }
}
