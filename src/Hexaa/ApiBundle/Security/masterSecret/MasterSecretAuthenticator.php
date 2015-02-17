<?php
namespace Hexaa\ApiBundle\Security\masterSecret;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;


class MasterSecretAuthenticator implements SimplePreAuthenticatorInterface {
    protected $userProvider;
    protected $httpUtils;
    protected $loginlog;
    protected $logLbl;

    public function __construct(MasterSecretUserProvider $userProvider, HttpUtils $httpUtils, Logger $loginlog) {
        $this->userProvider = $userProvider;
        $this->httpUtils = $httpUtils;
        $this->loginlog = $loginlog;
        $this->logLbl = "[masterSecretAuth] ";
    }

    public function createToken(Request $request, $providerKey) {
        if (!$request->request->has('apikey')) {
            $this->loginlog->error($this->logLbl . "API key not found in request");
            throw new HttpException(400, 'No API key found');
        }
        $apiKey = $request->request->get('apikey');
        $request->request->remove('apikey');

        return new PreAuthenticatedToken(
            'anon.',
            $apiKey,
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey) {
        $apiKey = $token->getCredentials();
        $username = $this->userProvider->getUsernameForApiKey($apiKey);


        if (!$username) {
            throw new HttpException(403, 'Invalid api key.');
        }

        $user = $this->userProvider->loadUserByUsername($username);

        return new PreAuthenticatedToken(
            $user,
            $apiKey,
            $providerKey,
            $user->getRoles()
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey) {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }
}