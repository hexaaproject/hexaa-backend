<?php

/**
 * Copyright 2014-2018 MTA SZTAKI, ugyeletes@sztaki.hu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\ApiBundle\Security\personalApiKey;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

class PersonalApiKeyAuthenticator implements SimplePreAuthenticatorInterface
{
    protected $userProvider;
    protected $httpUtils;
    protected $loginlog;
    protected $logLbl;
    protected $authCookieName;

    public function __construct(
      PersonalApiKeyUserProvider $userProvider,
      HttpUtils $httpUtils,
      Logger $loginlog,
      $authCookieName
    ) {
        $this->userProvider = $userProvider;
        $this->httpUtils = $httpUtils;
        $this->loginlog = $loginlog;
        $this->logLbl = "[personalApiKeyAuth] ";
        $this->authCookieName = $authCookieName;
    }

    public function createToken(Request $request, $providerKey)
    {
        if ($request->cookies->has($this->authCookieName) && $request->cookies->get($this->authCookieName) !== null) {
            $token = $request->cookies->get($this->authCookieName);
        } else {
            if ($request->headers->has('X-HEXAA-AUTH') && $request->headers->get('X-HEXAA-AUTH') !== null) {
                $token = $request->headers->get('X-HEXAA-AUTH');
            } else {
                $this->loginlog->error($this->logLbl."token not found");
                throw new HttpException(401, 'No API key found');
            }
        }


        return new PreAuthenticatedToken(
          'anon.',
          $token,
          $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $apiKey = $token->getCredentials();

        $user = $this->userProvider->loadUserByUsername($apiKey);

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
