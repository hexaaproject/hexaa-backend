<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class RolePrincipalType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('expiration', 'datetime', array('widget' => 'single_text'))
            ->add('principal', 'entity', array(
                'class'    => 'HexaaStorageBundle:Principal',
                'property' => 'id',
                'label'    => 'principal_id'))
            ->add('role', 'entity', array(
                'class'    => 'HexaaStorageBundle:Role',
                'property' => 'id',
                'label'    => 'role_id'));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\RolePrincipal',
            'csrf_protection' => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return '';
    }

}
