<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ServiceAttributeValuePrincipalType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('isAllowed')
            //->add('attributeValuePrincipal', array('type' => new AttributeValuePrincipal()))
            //->add('service', array('type' => new ServiceType()))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hexaa\StorageBundle\Entity\ServiceAttributeValuePrincipal'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }
}
