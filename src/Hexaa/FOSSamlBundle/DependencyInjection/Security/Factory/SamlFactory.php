<?php
/**
 * Description of SamlListener
 *
 * @author Paulo Dias
 */
namespace Hexaa\FOSSamlBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class SamlFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.saml.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('saml.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProvider));

        $listenerId = 'security.authentication.listener.saml.'.$id;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('saml.security.authentication.listener'));
        
        /*****/
        
        //$logoutId = 'saml.logout.handler.saml.'.$id;
        //$logoutlistener = $container->setDefinition($logoutId, new DefinitionDecorator('saml.logout.handler'));
        
        /*$logoutId = 'saml.security.logout_listener.saml.'.$id;
        $logoutlistener = $container->setDefinition($logoutId, new DefinitionDecorator('saml.security.logout_listener'));*/

        //return array($providerId, $listenerId, $logoutId, $defaultEntryPoint);
        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'saml';
    }

    public function addConfiguration(NodeDefinition $node)
    {}
}