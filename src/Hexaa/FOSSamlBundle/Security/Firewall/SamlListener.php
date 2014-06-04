<?php
/**
 * Description of SamlListener
 *
 * @author Paulo Dias
 */
namespace Hexaa\FOSSamlBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Hexaa\FOSSamlBundle\Security\Authentication\Token\SamlUserToken;

class SamlListener implements ListenerInterface
{
    protected $securityContext;
    protected $authenticationManager;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager)
    {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
    }

    public function handle(GetResponseEvent $event)
    {
        //$request = $event->getRequest();
        
        $attributes = array();
        $auth = new \SimpleSAML_Auth_Simple('default-sp'); 
        $auth->requireAuth(); 
        $attributes = $auth->getAttributes();

        $token = new SamlUserToken();
        $token->setUser($attributes['eduPersonPrincipalName'][0]);

        try {
            $returnValue = $this->authenticationManager->authenticate($token);

            if ($returnValue instanceof TokenInterface) {
                return $this->securityContext->setToken($returnValue);
            } else if ($returnValue instanceof Response) {
                return $event->setResponse($returnValue);
            }
        } catch (AuthenticationException $e) {
            // you might log something here
        }

        $response = new Response();
        $response->setStatusCode(403);
        $event->setResponse($response);
    }
}