<?php
/**
 * Description of SamlUserProvider
 *
 * @author Paulo Dias
 */
namespace Hexaa\FOSSamlBundle\Security\User;

use Hexaa\StorageBundle\Entity\Principal;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class SamlUserProvider implements UserProviderInterface
{
    private $container;
    
    public function __construct($container)
    {
      $this->container = $container;
    }
    public function loadUserByUsername($username)
    {
        $auth = new \SimpleSAML_Auth_Simple('default-sp'); 
        $auth->requireAuth(); 
        
        if ($auth->isAuthenticated()) {
            $attributes = $auth->getAttributes();
            
            //$roles[] = 'ROLE_'.mb_strtoupper($attributes['eduPersonPrimaryAffiliation'][0]);
            $roles[] = 'ROLE_USER';

            $user = new SamlUser($username, $roles);
            
            foreach($attributes as $key => $attribute){
                if(count($attribute)==1) {
                    $user->setAttribute($key, $attribute[0]);
                }else{
                    $user->setAttribute($key, $attribute);
                }
            }
            
            //TODO Login hook caller ide, amíg nincs, így biztosítjuk, hogy Principal objektuma a usernek
            

            
            $em = $this->container->get("doctrine")->getManager();
            $p = $em->getRepository('HexaaStorageBundle:Principal')
	      ->findOneByFedid($user->getAttribute('uid'));
            if (!$p) {
	      $p = new Principal();
	      $p->setFedid($user->getAttribute('uid'));
	    }	    
	    $date = new \DateTime();
	    if (!$p->getTokenExpire()) {
	      $tokenExp = new \DateTime();
	      $tokenExp->modify('-2 hour');
	    } else {
	      $tokenExp = $p->getTokenExpire();
	    }
	    $diff = $tokenExp->diff($date, true);
	    if ((!$p->getToken()) || (strlen($p->getToken())<2) || ($date>$tokenExp) || ($diff->format("H")>1))
	    {
	      $date->modify('+1 hour');
	      $p->setToken(hash('sha256', $p->getFedid().$date->format('Y-m-d H:i:s')));
	      $p->setTokenExpire($date);
	      $em->persist($p);
	      $em->flush();
	    }
            $user->setPrincipal($p);
            
            return $user;
        } else {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof SamlUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Hexaa\FOSSamlBundle\Security\User\SamlUser';
    }
}
