<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ServiceAttributeSpecCompleteType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('is_public', "checkbox", array('required' => false))
            ->add('service', 'entity', array(
                'class'    => 'HexaaStorageBundle:service',
                'property' => 'id'))
            ->add('attribute_spec', 'entity', array(
                'class'    => 'HexaaStorageBundle:AttributeSpec',
                'property' => 'id'));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\ServiceAttributeSpec',
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
