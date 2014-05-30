<?php
/**
 * Description of SamlLogoutListener
 *
 * @author Paulo Dias
 */
namespace Hexaa\FOSSamlBundle\Security\Logout;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
//use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Routing\Router;

class SamlLogoutHandler implements LogoutSuccessHandlerInterface//LogoutHandlerInterface
{
    /*public function __construct()
    {
    }*/
    protected $router;
    protected $security;
    
    public function __construct(Router $router, SecurityContext $security)
    {
        $this->router = $router;
        $this->security = $security;
    }
    
    /*protected $router; 
   
    public function __construct(Router $router) 
    { 
        $this->router = $router; 
    }*/

    /*public function logout(Request $request, Response $response, TokenInterface $token) 
    {
        var_dump('AAAA');
        
        $auth = new \SimpleSAML_Auth_Simple('default-sp'); 
	$auth->requireAuth();

	if($auth->isAuthenticated()) {
            $auth->logout('http://www.Hexaa.pt');
	}
    }*/
    
    public function onLogoutSuccess(Request $request)
    {
        //return new RedirectResponse($this->router->generate('homepage'));
        var_dump('AAAA');
    }
}