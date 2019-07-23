<?php

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

abstract class AbstractController extends SymfonyAbstractController implements ServiceAnnotationInterface
{
    /**
     * Initializes the Contao framework.
     */
    protected function initializeContao()
    {
        $this->get('contao.framework')->initialize();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;

        return $services;
    }
}
