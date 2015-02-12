<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AttributeValuePrincipalType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            //->add('is_default')
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
            //->add('services')
            ->add('attribute_spec', 'entity', array(
                'class'    => 'HexaaStorageBundle:AttributeSpec',
                'property' => 'id',
                'label'    => 'attribute_spec_id'))
            ->add('principal', 'entity', array(
                'class'    => 'HexaaStorageBundle:Principal',
                'property' => 'id',
                'label'    => 'principal_id'))
            //->add('principal')
            //->add('attributeSpec')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\AttributeValuePrincipal',
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
