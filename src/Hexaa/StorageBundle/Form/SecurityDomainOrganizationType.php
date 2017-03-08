<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SecurityDomainOrganizationType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add(
            'organizations',
            'collection',
            array(
              "type"         => 'entity',
              "options"      => array(
                "class"    => 'HexaaStorageBundle:Organization',
                "property" => 'id',
              ),
              "allow_delete" => true,
              "allow_add"    => true,
              "description"  => "IDs of organizations to link to security domain",
              "by_reference" => false,
              "delete_empty" => true,
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
            'data_class'      => 'Hexaa\StorageBundle\Entity\SecurityDomain',
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
