<?php

namespace Hexaa\ApiBundle\Security\personalApiKey;

use Doctrine\ORM\EntityManager;
use Hexaa\ApiBundle\Security\HexaaUser;
use Hexaa\StorageBundle\Entity\Principal;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class PersonalApiKeyUserProvider implements UserProviderInterface {
    protected $loginlog;
    protected $modlog;
    protected $logLbl;
    protected $em;

    public function __construct(EntityManager $em, Logger $loginlog, Logger $modlog) {
        $this->em = $em;
        $this->loginlog = $loginlog;
        $this->modlog = $modlog;
        $this->logLbl = "[personalApiKeyAuth] ";
    }

    public function getPrincipalForApiKey($apiKey) {
        $p = $this->em->getRepository("HexaaStorageBundle:Principal")->findOneByPersonalToken($apiKey);
        if (!($p instanceof Principal)) {
            $this->loginlog->error($this->logLbl . "Token not found in database");
            throw new HttpException(401, 'Invalid token!');
        } else {
            $token = $p->getToken();
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
            if ($date > $token->getTokenExpire()) {
                $this->loginlog->error($this->logLbl . "Token expired for principal with fedid=" . $p->getFedid());
                throw new HttpException(401, 'Token expired');
            } else {
                $this->loginlog->info($this->logLbl . "User " . $p->getFedid() . " successfully authenticated with a token of " . $token->getMasterkey() . " masterkey");
                $date->modify('+1 hour');
                $token->setTokenExpire($date);
                $this->em->persist($token);
                $this->em->flush();
                $this->loginlog->info($this->logLbl . "Token expiration reset for user id=" . $p->getId());
                $this->modlog->info($this->logLbl . "Token expiration reset for user id=" . $p->getId());

                return $p;
            }
        }
    }

    public function loadUserByUsername($apikey) {
        /* @var $p Principal */
        $p = $this->getPrincipalForApiKey($apikey);
        $securityRoles = array('ROLE_API');

        return new HexaaUser(
            $p->getFedid(), null, null, $p,
            // the roles for the user - you may choose to determine
            // these dynamically somehow based on the user
            $securityRoles
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
        return 'Hexaa\ApiBundle\Security\HexaaUser' === $class;
    }

}
