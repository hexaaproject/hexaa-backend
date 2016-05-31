<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class RolePrincipalType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('expiration', 'datetime', array('widget' => 'single_text'))
            ->add('principal', 'entity', array(
                'class'    => 'HexaaStorageBundle:Principal',
                'property' => 'id',
                'label'    => 'principal_id'
            ))
            ->add('role', 'entity', array(
                'class'    => 'HexaaStorageBundle:Role',
                'property' => 'id',
                'label'    => 'role_id'
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\RolePrincipal',
            'csrf_protection' => false
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
