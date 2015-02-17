<?php
namespace Hexaa\ApiBundle\Security\personalApiKey;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

class PersonalApiKeyAuthenticator implements SimplePreAuthenticatorInterface {
    protected $userProvider;
    protected $httpUtils;
    protected $loginlog;
    protected $logLbl;

    public function __construct(PersonalApiKeyUserProvider $userProvider, HttpUtils $httpUtils, Logger $loginlog) {
        $this->userProvider = $userProvider;
        $this->httpUtils = $httpUtils;
        $this->loginlog = $loginlog;
        $this->logLbl = "[personalApiKeyAuth] ";
    }

    public function createToken(Request $request, $providerKey) {
        if (!$request->headers->get('X-HEXAA-AUTH')) {
            $this->loginlog->error($this->logLbl . "token not found in request");
            throw new HttpException(403, 'No API key found');
        }


        return new PreAuthenticatedToken(
            'anon.',
            $request->headers->get('X-HEXAA-AUTH'),
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey) {
        $apiKey = $token->getCredentials();

        $user = $this->userProvider->loadUserByUsername($apiKey);

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