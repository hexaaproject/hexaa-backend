<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HookType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('url')
            ->add('type')
            ->add('service', 'entity', array(
                'class'    => 'HexaaStorageBundle:Service',
                'property' => 'id',
                'label'    => 'service_id'
            ))
            ->add('organization', 'entity', array(
                'class'    => 'HexaaStorageBundle:Organization',
                'property' => 'id',
                'label'    => 'organization_id'
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\Hook',
            'csrf_protection' => false
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }
}
