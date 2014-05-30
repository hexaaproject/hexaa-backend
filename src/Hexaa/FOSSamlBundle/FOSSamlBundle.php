<?php
/**
 * Description of SamlListener
 *
 * @author Paulo Dias
 */
namespace Hexaa\FOSSamlBundle;

use Hexaa\FOSSamlBundle\DependencyInjection\Security\Factory\SamlFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FOSSamlBundle extends Bundle 
{
  public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SamlFactory());
        
    }
}
