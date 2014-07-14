<?php
namespace Hexaa\ApiBundle\Security\personalApiKey;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Hexaa\StorageBundle\Entity\Principal;

class PersonalApiKeyUserProvider implements UserProviderInterface
{

    public function __construct($container)
    {
      $this->container = $container;
    }
    
    public function getUsernameForApiKey($apiKey)
    {
        $em = $this->container->get("doctrine")->getManager();
        $p = $em->getRepository("HexaaStorageBundle:Principal")->findOneByToken($apiKey);
        if (!($p instanceof Principal))        
        {
	  throw new HttpException(403, 'Invalid token!');
        } else {
          $date = new \DateTime();
	  $tokenExp = $p->getTokenExpire();
	  $diff = $tokenExp->diff($date, true);
	  if (($date>$tokenExp) || ($diff->format("H")>1)) {
	    throw new HttpException(401, 'Token expired');
	  } else {
	    $username = $p->getFedid();
	    return $username;
	  }
        }
    }

    public function loadUserByUsername($username)
    {
        return new User(
            $username,
            null,
            // the roles for the user - you may choose to determine
            // these dynamically somehow based on the user
            array('ROLE_API')
        );
    }

    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return 'Symfony\Component\Security\Core\User\User' === $class;
    }
} 
