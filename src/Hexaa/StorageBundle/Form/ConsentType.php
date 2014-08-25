<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ConsentType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('enable_entitlements')
                ->add('expiration', 'datetime', array('widget' => 'single_text'))
                //->add('createdAt')
                //->add('updatedAt')
                ->add('enabled_attribute_specs', 'collection', array('type' => 'integer', "allow_delete" => true, "allow_add" => true, "delete_empty" => true))
                ->add('principal', 'entity', array(
                    'class' => 'HexaaStorageBundle:Principal',
                    'property' => 'id',
                    'label' => 'principal_id'))
                ->add('service', 'entity', array(
                    'class' => 'HexaaStorageBundle:Service',
                    'property' => 'id',
                    'label' => 'service_id'
                ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Hexaa\StorageBundle\Entity\Consent',
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
