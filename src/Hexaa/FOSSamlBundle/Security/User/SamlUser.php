<?php
/**
 * Description of SamlListener
 *
 * @author Paulo Dias
 */
namespace Hexaa\FOSSamlBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\Role\Role;

class SamlUser implements UserInterface
{
    private $principal;
    private $username;
    private $roles;
    private $attributes;

    public function __construct($username, array $roles = array())
    {
        $this->username = $username;
        //$this->roles = $roles;
        $this->attributes = array();
        
      
        
        $this->roles = array();
        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = new Role($role);
            } elseif (!$role instanceof RoleInterface) {
                throw new \InvalidArgumentException(sprintf('$roles must be an array of strings, or RoleInterface instances, but got %s.', gettype($role)));
            }

            $this->roles[] = $role;
        }
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword(){}

    public function getSalt(){}

    public function getUsername()
    {
        return $this->username;
    }

    public function eraseCredentials(){}

    public function equals(UserInterface $user)
    {
        if (!$user instanceof SamlUser) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
    
    /**
     * Returns the token attributes.
     *
     * @return array The token attributes
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Sets the token attributes.
     *
     * @param array $attributes The token attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }
    
    

    /**
     * Returns true if the attribute exists.
     *
     * @param  string  $name  The attribute name
     *
     * @return Boolean true if the attribute exists, false otherwise
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }
    
    /**
     * Returns a attribute value.
     *
     * @param string $name The attribute name
     *
     * @return mixed The attribute value
     *
     * @throws \InvalidArgumentException When attribute doesn't exist for this token
     */
    public function getAttribute($name)
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf('This token has no "%s" attribute.', $name));
        }

        return $this->attributes[$name];
    }
    
    /**
     * Sets a attribute.
     *
     * @param string $name  The attribute name
     * @param mixed  $value The attribute value
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    /**
     * Returns the user's principal object
     *
     * @return mixed The attribute value
     *
     * @throws \InvalidArgumentException When attribute doesn't exist for this token
     */
    public function getPrincipal()
    {
        return $this->principal;
    }
    
    /**
     * Sets a principal.
     *
     * @param mixed  $principal The principal object of the user
     */
    public function setPrincipal($principal)
    {
        $this->principal = $principal;        
    }
}