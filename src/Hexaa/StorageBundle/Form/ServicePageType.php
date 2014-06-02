<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ServicePageType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
	    ->add('id')
            ->add('properties', array("type" => new GuiServiceType()))
            ->add('managers', 'collection', array('type' => new PrincipalType()))
            ->add('attributeSpecifications', 'collection', array('type' => new ServiceAttributeValiePrincipalType()))
            ->add('entitlements', 'collection', array('type' => new GuiEntitlementType()))
            ->add('entitlementPacks', 'collection', array('type' => new GuiOrganizationEntitlementPackType()))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hexaa\StorageBundle\Entity\ServicePage',
            'csrf_protection' => false,
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
