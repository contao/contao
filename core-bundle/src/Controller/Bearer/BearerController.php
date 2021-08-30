<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Controller\Bearer;

use Contao\BackendUser;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Jwt\Jwt;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\System;
use Contao\User;
use Contao\UserModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class BearerController extends AbstractController
{
    protected $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @Route("/bearerFrontend/auth", name="contao_bearerFrontend_auth", defaults={"_scope" = "frontend", "_token_check" = false}, methods="POST")
     */
    public function memberAuth(Request $request): JsonResponse
    {
        try {

            $this->framework->initialize();

            $data = (array)\json_decode($request->getContent(), true);
            $response = $this->generateAuthResponse($data, false);

            return (new JsonResponse($response));

        } catch (\Exception $exception) {
            return (new JsonResponse($exception->getMessage(), 400));
        }
    }

    /**
     * @Route("/bearerBackend/auth", name="contao_bearerBackend_auth", defaults={"_scope" = "frontend", "_token_check" = false}, methods="POST")
     */
    public function userAuth(Request $request): JsonResponse
    {
        try {

            $this->framework->initialize();

            $data = (array)\json_decode($request->getContent(), true);
            $response = $this->generateAuthResponse($data, true);

            return (new JsonResponse($response));

        } catch (\Exception $exception) {
            return (new JsonResponse($exception->getMessage(), 400));
        }
    }

    /**
     * @Route("/bearerFrontend/memberInfo", name="contao_bearerFrontend_memberinfo", defaults={"_scope" = "bearerFrontend", "_token_check" = false}, methods="GET")
     */
    public function memberInfo(Request $request, UserInterface $user): JsonResponse
    {
        try {

            $this->framework->initialize();

            if ($user instanceof FrontendUser) {

                return (new JsonResponse($user->getData()));

            } else {
                throw new \Exception('invalid user');
            }

        } catch (\Exception $exception) {
            return (new JsonResponse($exception->getMessage(), 400));
        }
    }

    /**
     * @Route("/bearerBackend/userInfo", name="contao_bearerBackend_userinfo", defaults={"_scope" = "bearerBackend", "_token_check" = false}, methods="GET")
     */
    public function userInfo(Request $request, UserInterface $user): JsonResponse
    {
        try {

            $this->framework->initialize();

            if ($user instanceof BackendUser) {

                return (new JsonResponse($user->getData()));

            } else {
                throw new \Exception('invalid user');
            }

        } catch (\Exception $exception) {
            return (new JsonResponse($exception->getMessage(), 400));
        }
    }

    /**
     * @Route("/bearerFrontend/refresh", name="contao_bearerFrontend_refresh", defaults={"_scope" = "bearerFrontend", "_token_check" = false}, methods="POST")
     *
     * @param Request $request
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function refreshMember(Request $request, UserInterface $user): JsonResponse
    {
        try {

            $this->framework->initialize();

            $data = (array)\json_decode($request->getContent(), true);
            $response = $this->refreshToken($data, $user, false);

            return (new JsonResponse($response));

        } catch (\Exception $exception) {
            return (new JsonResponse($exception->getMessage(), 400));
        }
    }

    /**
     * @Route("/bearerBackend/refresh", name="contao_bearerBackend_refresh", defaults={"_scope" = "bearerBackend", "_token_check" = false}, methods="POST")
     *
     * @param Request $request
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function refreshUser(Request $request, UserInterface $user): JsonResponse
    {
        try {

            $this->framework->initialize();

            $data = (array)\json_decode($request->getContent(), true);
            $response = $this->refreshToken($data, $user, true);

            return (new JsonResponse($response));

        } catch (\Exception $exception) {
            return (new JsonResponse($exception->getMessage(), 400));
        }
    }

    /**
     * @Route("/bearerFrontend/logout", name="contao_bearer_logout", defaults={"_scope" = "bearerFrontend", "_token_check" = false}, methods="GET")
     *
     * @param Request $request
     * @param UserInterface $user
     * @return JsonResponse
     * @throws \Exception
     */
    public function logoutMember(Request $request, UserInterface $user): JsonResponse
    {
        // For future use - but therefore the tokens has to be stored in database and auth should compare to existing database entries
        throw new \Exception('not supported');
    }

    /**
     * @Route("/bearerBackend/logout", name="contao_bearer_logout", defaults={"_scope" = "bearerBackend", "_token_check" = false}, methods="GET")
     *
     * @param Request $request
     * @param UserInterface $user
     * @return JsonResponse
     * @throws \Exception
     */
    public function logoutUser(Request $request, UserInterface $user): JsonResponse
    {
        // For future use - but therefore the tokens has to be stored in database and auth should compare to existing database entries
        throw new \Exception('not supported');
    }

    /**
     * @param array $data
     * @param bool $isBackendUser
     * @return array
     * @throws \Exception
     */
    private function generateAuthResponse(array $data, bool $isBackendUser): array
    {
        if (!\array_key_exists('username', $data) || !\array_key_exists('password', $data)) {
            throw new \Exception('invalid key-parameters for auth');
        }

        $ttl = 3000;
        if (\array_key_exists('ttl', $data)) {
            $ttl = (int)$data['ttl'];
        }

        $username = (string)$data['username'];
        $password = (string)$data['password'];

        $encoder = System::getContainer()->get('security.encoder_factory')->getEncoder(User::class);

        if ($isBackendUser) {

            $userObject = UserModel::findByUsername($username);
            if ($userObject === null) {
                throw new \Exception('user not found');
            }

            if (!$encoder->isPasswordValid($userObject->password, $password, null)) {
                throw new \Exception("error auth - invalid password for username:" . $username);
            }

        } else {

            $memberObject = MemberModel::findByUsername($username);
            if ($memberObject === null) {
                throw new \Exception('user not found');
            }

            if (!$encoder->isPasswordValid($memberObject->password, $password, null)) {
                throw new \Exception("error auth - invalid password for username:" . $username);
            }

        }

        return [
            'username' => $username,
            'token' => Jwt::generate(\base64_encode($username), $ttl, array('username' => $username)),
            'refresh_token' => Jwt::generate(\base64_encode($username), $ttl, array('username' => $username, 'isRefreshToken' => true))
        ];
    }

    /**
     * @param array $data
     * @param UserInterface $user
     * @param bool $isBackendUser
     * @return array
     * @throws \Exception
     */
    public function refreshToken(array $data, UserInterface $user, bool $isBackendUser): array
    {
        if (!\array_key_exists('refresh_token', $data)) {
            throw new \Exception('invalid key-parameters for refresh token');
        }

        $ttl = 3000;
        if (\array_key_exists('ttl', $data)) {
            $ttl = (int)$data['ttl'];
        }

        $refreshToken = (string)$data['refresh_token'];

        $username = $user->getUsername();

        try {

            if (Jwt::validateAndVerify($refreshToken, \base64_encode($username)) === false) {
                throw new \Exception('refresh_token not valid');
            }

            $tokenUsername = Jwt::getClaim($refreshToken, 'username');

            if ($tokenUsername == null || $tokenUsername == '') {
                throw new \Exception('refresh_token does not match with username');
            }

            $isRefreshToken = Jwt::getClaim($refreshToken, 'isRefreshToken');
            if (!$isRefreshToken) {
                throw new \Exception('invalid refresh_token');
            }

        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }

        return [
            'username' => $username,
            'token' => Jwt::generate(\base64_encode($username), $ttl, array('username' => $username)),
            'refresh_token' => Jwt::generate(\base64_encode($username), $ttl, array('username' => $username, 'isRefreshToken' => true))
        ];
    }

}
