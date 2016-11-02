<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add(
            'organization',
            "entity",
            array(
              "class"       => 'HexaaStorageBundle:Organization',
              "property"    => 'id',
              "description" => "ID of organization to link",
            )
          )
          ->add(
            'service',
            "entity",
            array(
              "class"       => 'HexaaStorageBundle:Service',
              "property"    => 'id',
              "description" => "ID of service to link",
            )
          )
          ->add(
            'entitlements',
            'collection',
            array(
              "type"         => 'entity',
              "options"      => array(
                "class"    => 'HexaaStorageBundle:Entitlement',
                "property" => 'id',
              ),
              "allow_delete" => true,
              "allow_add"    => true,
              "description"  => "IDs of entitlements to link",
              "by_reference" => false,
            )
          )
          ->add(
            'entitlement_packs',
            'collection',
            array(
              "type"         => 'entity',
              "options"      => array(
                "class"    => 'HexaaStorageBundle:EntitlementPack',
                "property" => 'id',
              ),
              "allow_delete" => true,
              "allow_add"    => true,
              "description"  => "IDs of entitlement packages to link",
              "by_reference" => false,
            )
          );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
          array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\Link',
            'csrf_protection' => false,
          )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }

}
