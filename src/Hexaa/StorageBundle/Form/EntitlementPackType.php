<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntitlementPackType extends AbstractType
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
          ->add('type');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
          array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\EntitlementPack',
            'csrf_protection' => false,
          )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }
}
