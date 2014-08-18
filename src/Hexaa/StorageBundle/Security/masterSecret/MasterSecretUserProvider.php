<?php

namespace Hexaa\StorageBundle\Security\masterSecret;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Monolog\Logger;

class MasterSecretUserProvider implements UserProviderInterface {

    private $secret;
    protected $loginlog;
    protected $logLbl;

    public function __construct($secret, Logger $loginlog) {
        $this->secret = $secret;
        $this->loginlog = $loginlog;
        $this->logLbl = "[masterSecretAuth] ";
    }

    public function getUsernameForApiKey($apiKey) {
        // Generate hashes to compare with
        $time = new \DateTime();
        date_timezone_set($time, new \DateTimeZone('UTC'));
        $stamp1 = $time->format('Y-m-d H:i');
        $hash1 = hash('sha256', $this->secret . $stamp1);
        $time->sub(new \DateInterval('PT1M'));
        $stamp2 = $time->format('Y-m-d H:i');
        $hash2 = hash('sha256', $this->secret . $stamp2);

        // Compare, and authenticate or deny entry
        if ($apiKey == $hash1 || $apiKey == $hash2) {
            $this->loginlog->info($this->logLbl . "master secret authentication successful");
            $username = "master"; // some dummy username
            return $username;
        } else {
            $this->loginlog->error($this->logLbl . "API key is invalid or expired");
            throw new HttpException(403, "Invalid api key.");
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
