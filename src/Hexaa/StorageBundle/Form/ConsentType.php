<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsentType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('enable_entitlements', "checkbox")
            //->add('expiration', 'datetime', array('widget' => 'single_text'))
            //->add('createdAt')
            //->add('updatedAt')
            ->add('enabled_attribute_specs', 'collection', array(
                "type"         => "entity",
                "options"      => array(
                    "class"    => 'HexaaStorageBundle:AttributeSpec',
                    "property" => "id"
                ),
                "allow_delete" => true,
                "allow_add"    => true,
            ))
            ->add('principal', 'entity', array(
                'class'    => 'HexaaStorageBundle:Principal',
                'property' => 'id',
                'label'    => 'principal_id'))
            ->add('service', 'entity', array(
                'class'    => 'HexaaStorageBundle:Service',
                'property' => 'id',
                'label'    => 'service_id'
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\Consent',
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
