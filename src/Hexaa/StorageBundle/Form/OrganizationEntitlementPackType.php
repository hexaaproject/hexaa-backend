<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OrganizationEntitlementPackType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('status')
            ->add('createdAt')
            ->add('acceptAt')
            ->add('organization', array('type' => new OrganizationType()))
            ->add('entitlementPack', array('type' => new ServiceType()))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hexaa\StorageBundle\Entity\OrganizationEntitlementPack'
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
