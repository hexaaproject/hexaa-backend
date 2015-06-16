<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationManagerType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('managers', 'collection', array(
                "type"         => 'entity',
                "options"      => array(
                    "class"    => 'HexaaStorageBundle:Principal',
                    "property" => 'id'
                ),
                "allow_delete" => true,
                "allow_add"    => true,
                "description"  => "IDs of managers to link to Organization"
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class'        => 'Hexaa\StorageBundle\Entity\Organization',
            'validation_groups' => array('setmanager'),
            'csrf_protection'   => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return '';
    }

}
