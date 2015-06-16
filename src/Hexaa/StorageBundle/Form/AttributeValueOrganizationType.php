<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributeValueOrganizationType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('value')
            ->add('services', 'collection', array(
                "type"         => 'entity',
                "options"      => array(
                    "class"    => 'HexaaStorageBundle:Service',
                    "property" => 'id'
                ),
                "allow_delete" => true,
                "allow_add"    => true,
            ))
            ->add('attribute_spec', 'entity', array(
                'class'    => 'HexaaStorageBundle:AttributeSpec',
                'property' => 'id',
                'label'    => 'attribute_spec_id'))
            ->add('organization', 'entity', array(
                'class'    => 'HexaaStorageBundle:Organization',
                'property' => 'id',
                'label'    => 'organization_id'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\AttributeValueOrganization',
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
