<?php
namespace Hexaa\StorageBundle\Security\sspApiKey;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class SspApiKeyUserProvider implements UserProviderInterface
{
    private $secret;
    
    public function __construct($secret) {
        $this->secret = $secret;
    }
    public function getUsernameForApiKey($apiKey)
    {
        // Look up the username based on the token in the database, via
        // an API call, or do something entirely different
        date_default_timezone_set('UTC');
        $time = new \DateTime();
        $stamp1 = $time->format('Y-m-d H:i');
        $hash1 = hash('sha256',$this->secret.$stamp1);
        $time->sub(new \DateInterval('PT1M'));
        $stamp1 = $time->format('Y-m-d H:i');
        $hash2 = hash('sha256',$this->secret.$stamp1);
        //var_dump($apiKey, $hash1, $hash2, $stamp1);
        if ($apiKey == $hash1 || $apiKey == $hash2)
        
        //if ($apiKey == "123")  //DEBUG VALUE TODO
        {
	  $username = "ssp";
	  return $username;
        } else {
	  throw new HttpException(403, "Invalid api key.");
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
