<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationType extends AbstractType
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
          ->add('isolate_members', "checkbox")
          ->add('isolate_role_members', "checkbox")
          ->add(
            'default_role',
            'entity',
            array(
              'class'    => 'HexaaStorageBundle:Role',
              'property' => 'id',
              'label'    => 'default_role_id',
              'required' => false,
            )
          )/*
            ->add('tags', 'collection', array(
                "type"    => new TextType()
            ))*/
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
          array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\Organization',
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
