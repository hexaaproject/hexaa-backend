<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleEntitlementType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
              "description"  => "IDs of entities to link to Role",
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
            'data_class'      => 'Hexaa\StorageBundle\Entity\Role',
            'csrf_protection' => false,
          )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }

}
