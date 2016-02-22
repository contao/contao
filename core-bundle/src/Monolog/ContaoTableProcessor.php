<?php

namespace Contao\CoreBundle\Monolog;

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ContaoTableProcessor
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Constructor.
     *
     * @param RequestStack          $requestStack
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        $request = $this->requestStack->getCurrentRequest();

        $record['extra']['ip']       = $request->getClientIp(); // TODO anonymize IP
        $record['extra']['browser']  = $request->server->get('HTTP_USER_AGENT');
        $record['extra']['username'] = $this->getUsername();
        $record['extra']['function'] = (string) $record['context']['function'];

        unset($record['context']['function']);

        return $record;
    }

    /**
     * @return string
     */
    private function getUsername()
    {
        $token = $this->tokenStorage->getToken();

        return null === $token ? 'N/A' : $token->getUsername();
    }
}
