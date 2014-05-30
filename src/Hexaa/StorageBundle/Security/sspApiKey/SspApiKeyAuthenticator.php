<?php
namespace Hexaa\StorageBundle\Security\sspApiKey;

use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class SspApiKeyAuthenticator implements SimplePreAuthenticatorInterface
{
    protected $userProvider;
    protected $httpUtils;

    public function __construct(SspApiKeyUserProvider $userProvider, HttpUtils $httpUtils)
    {
        $this->userProvider = $userProvider;
        $this->httpUtils = $httpUtils;
    }

    public function createToken(Request $request, $providerKey)
    {
        if (!$request->query->has('apikey')) {
            throw new BadCredentialsException('No API key found');
        }
        return new PreAuthenticatedToken(
            'anon.',
            $request->query->get('apikey'),
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $apiKey = $token->getCredentials();
        $username = $this->userProvider->getUsernameForApiKey($apiKey);
	    

        if (!$username) {
	    throw new AccessDeniedException(sprintf('Invalid token.'));
        }

        $user = $this->userProvider->loadUserByUsername($username);

        return new PreAuthenticatedToken(
            $user,
            $apiKey,
            $providerKey,
            $user->getRoles()
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }
}