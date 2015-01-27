<?php

namespace Hexaa\ApiBundle\Security\masterSecret;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Monolog\Logger;

class MasterSecretUserProvider implements UserProviderInterface {

    private $secrets;
    protected $loginlog;
    protected $logLbl;

    public function __construct($secrets, Logger $loginlog) {
        $this->secrets = $secrets;
        $this->loginlog = $loginlog;
        $this->logLbl = "[masterSecretAuth] ";
    }

    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function getUsernameForApiKey($apiKey) {
        $hadKey = false;
        $time = new \DateTime('now', new \DateTimeZone('UTC'));
        $time2 = new \DateTime('now', new \DateTimeZone('UTC'));
        $time2->sub(new \DateInterval('PT1M'));
        $stamp1 = $time->format('Y-m-d H:i');
        $stamp2 = $time2->format('Y-m-d H:i');
        foreach (array_keys($this->secrets) as $secret) {
            // Generate hashes to compare with
            $hash1 = hash('sha256', $secret . $stamp1);
            $hash2 = hash('sha256', $secret . $stamp2);

            // Compare, and authenticate or deny entry
            if ($apiKey == $hash1 || $apiKey == $hash2) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $hadKey = true;
                $this->loginlog->info($this->logLbl . "master secret authentication successful with master key ".$this->secrets[$secret]);
                $username = $this->secrets[$secret]; // use masterkey type as username
                return $username;
            }
        }
        if (!$hadKey) {
            $this->loginlog->error($this->logLbl . "API key is invalid or expired");
            throw new HttpException(401, "API key is invalid or expired");
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
