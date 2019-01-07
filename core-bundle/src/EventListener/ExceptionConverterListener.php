<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\ServiceUnavailableException as ContaoServiceUnavailableException;
use Doctrine\DBAL\Connection;
use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ExceptionConverterListener
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Maps known exceptions to HTTP exceptions.
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        if ($exception->getPrevious() instanceof ResourceNotFoundException && !$this->hasRootPages()) {
            $exception = new NoRootPageFoundException('No root page found', 0, $exception);
        }

        $class = $this->getTargetClass($exception);

        if (null === $class) {
            return;
        }

        if (null !== ($httpException = $this->convertToHttpException($exception, $class))) {
            $event->setException($httpException);
        }
    }

    private function getTargetClass(\Exception $exception): ?string
    {
        static $mapper = [
            AccessDeniedException::class => 'AccessDeniedHttpException',
            ForwardPageNotFoundException::class => 'InternalServerErrorHttpException',
            IncompleteInstallationException::class => 'InternalServerErrorHttpException',
            InsecureInstallationException::class => 'InternalServerErrorHttpException',
            InsufficientAuthenticationException::class => 'UnauthorizedHttpException',
            InternalServerErrorException::class => 'InternalServerErrorHttpException',
            InvalidRequestTokenException::class => 'BadRequestHttpException',
            NoActivePageFoundException::class => 'InternalServerErrorHttpException',
            NoLayoutSpecifiedException::class => 'InternalServerErrorHttpException',
            NoRootPageFoundException::class => 'InternalServerErrorHttpException',
            PageNotFoundException::class => 'NotFoundHttpException',
            ServiceUnavailableException::class => 'ServiceUnavailableHttpException',
            ContaoServiceUnavailableException::class => 'ServiceUnavailableHttpException',
        ];

        foreach ($mapper as $source => $target) {
            if ($exception instanceof $source) {
                return $target;
            }
        }

        return null;
    }

    private function convertToHttpException(\Exception $exception, string $target): ?HttpException
    {
        switch ($target) {
            case 'AccessDeniedHttpException':
                return new AccessDeniedHttpException($exception->getMessage(), $exception);

            case 'BadRequestHttpException':
                return new BadRequestHttpException($exception->getMessage(), $exception);

            case 'InternalServerErrorHttpException':
                return new InternalServerErrorHttpException($exception->getMessage(), $exception);

            case 'NotFoundHttpException':
                return new NotFoundHttpException($exception->getMessage(), $exception);

            case 'ServiceUnavailableHttpException':
                return new ServiceUnavailableHttpException(null, $exception->getMessage(), $exception);

            case 'UnauthorizedHttpException':
                return new UnauthorizedHttpException('', $exception->getMessage(), $exception);
        }

        return null;
    }

    private function hasRootPages(): bool
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('COUNT(*)')
            ->from('tl_page')
            ->where('type = :type')
            ->setParameter('type', 'root')
        ;

        return $qb->execute()->fetchColumn() > 0;
    }
}
