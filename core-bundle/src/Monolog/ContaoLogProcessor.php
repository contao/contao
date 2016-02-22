<?php

namespace Contao\CoreBundle\Monolog;

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ContaoLogProcessor
{
    const CONTEXT_CATEGORY = 'tl_log.action';
    const CONTEXT_FUNCTION = 'tl_log.func';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var IntrospectionProcessor
     */
    private $introspection;

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
        $this->setFunction($record);
        $this->setCategory($record);

        $request = $this->requestStack->getCurrentRequest();

        $record['extra']['tl_log.ip']       = $request->getClientIp(); // TODO anonymize IP
        $record['extra']['tl_log.browser']  = $request->server->get('HTTP_USER_AGENT');
        $record['extra']['tl_log.username'] = $this->getUsername();

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

    /**
     * @param $record
     */
    private function setFunction(&$record)
    {
        if (isset($record['context'][self::CONTEXT_FUNCTION])) {
            $record['extra'][self::CONTEXT_FUNCTION] = $record['context'][self::CONTEXT_FUNCTION];
            unset($record['context'][self::CONTEXT_FUNCTION]);

            return;
        }

        if (null === $this->introspection) {
            $this->introspection = new IntrospectionProcessor(Logger::DEBUG, ['Contao\System']);
        }

        $backtrace = call_user_func($this->introspection, $record);
        $record['extra'][self::CONTEXT_FUNCTION] = $backtrace['extra']['class'] . '::' . $backtrace['extra']['function'];
    }

    /**
     * @param $record
     */
    private function setCategory(&$record)
    {
        if (isset($record['context'][self::CONTEXT_CATEGORY])) {
            $record['extra'][self::CONTEXT_CATEGORY] = $record['context'][self::CONTEXT_CATEGORY];
            unset($record['context'][self::CONTEXT_CATEGORY]);

            return;
        }

        $extra[self::CONTEXT_CATEGORY] = $record['level'] >= 300 ? 'ERROR' : 'GENERAL';
    }
}
