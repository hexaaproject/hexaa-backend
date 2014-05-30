<?php
/**
 * Description of SamlLogoutListener
 *
 * @author Paulo Dias
 */
namespace Hexaa\FOSSamlBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\HttpUtils;

use Hexaa\FOSSamlBundle\Security\Authentication\Token\SamlUserToken;

class SamlLogoutListener implements ListenerInterface
{
    private $securityContext;
    private $httpUtils;
    private $logoutPath;
    private $targetUrl;
    private $handlers;
    private $successHandler;
    

    /**
     * Constructor
     *
     * @param SecurityContextInterface      $securityContext
     * @param HttpUtils                     $httpUtils        An HttpUtilsInterface instance
     * @param string                        $logoutPath       The path that starts the logout process
     * @param string                        $targetUrl        The URL to redirect to after logout
     * @param LogoutSuccessHandlerInterface $successHandler
     */
    public function __construct(SecurityContextInterface $securityContext, HttpUtils $httpUtils/*,  $logoutPath, $targetUrl = '/', LogoutSuccessHandlerInterface $successHandler = null*/)
    {
        $this->securityContext = $securityContext;
        $this->httpUtils = $httpUtils;
        /*$this->logoutPath = $logoutPath;
        $this->targetUrl = $targetUrl;
        $this->successHandler = $successHandler;*/
        $this->handlers = array();
    }

    public function handle(GetResponseEvent $event)
    {
        var_dump($event);
    }
}