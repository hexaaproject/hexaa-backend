<?php

namespace Hexaa\ApiBundle\Security\personalApiKey;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Hexaa\StorageBundle\Entity\Principal;
use Monolog\Logger;

class PersonalApiKeyUserProvider implements UserProviderInterface {
    protected $loginlog;
    protected $logLbl;

    public function __construct($container, Logger $loginlog) {
        $this->container = $container;
        $this->loginlog = $loginlog;
        $this->logLbl = "[personalApiKeyAuth] ";
    }

    public function getUsernameForApiKey($apiKey) {
        $em = $this->container->get("doctrine")->getManager();
        $p = $em->getRepository("HexaaStorageBundle:Principal")->findOneByToken($apiKey);
        if (!($p instanceof Principal)) {
            $this->loginlog->error($this->logLbl."token not found in database");
            throw new HttpException(403, 'Invalid token!');
        } else {
            $date = new \DateTime();
            date_timezone_set($date, new \DateTimeZone("UTC"));
            $tokenExp = $p->getTokenExpire();
            $diff = $tokenExp->diff($date, true);
            if (($date < $tokenExp) && ($diff->h > 1)) {
                $this->loginlog->error($this->logLbl."token expired.");
                throw new HttpException(401, 'Token expired');
            } else {
                $this->loginlog->info($this->logLbl."user ".$p->getFedid()." successfully authenticated");
                $username = $p->getFedid();
                return $username;
            }
        }
    }

    public function loadUserByUsername($username) {
        return new User(
                $username, null,
                // the roles for the user - you may choose to determine
                // these dynamically somehow based on the user
                array('ROLE_API')
        );
    }

    public function refreshUser(UserInterface $user) {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass($class) {
        return 'Symfony\Component\Security\Core\User\User' === $class;
    }

}
