<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SecurityDomainServiceType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('services', 'collection', array(
                "type"         => 'entity',
                "options"      => array(
                    "class"    => 'HexaaStorageBundle:Service',
                    "property" => 'id'
                ),
                "allow_delete" => true,
                "allow_add"    => true,
                "description"  => "IDs of services to link to security domain",
                "by_reference" => false
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\SecurityDomain',
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
