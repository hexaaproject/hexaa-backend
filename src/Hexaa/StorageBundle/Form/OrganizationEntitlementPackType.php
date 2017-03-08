<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationEntitlementPackType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          //->add('status', "choice", array("required" => false, "choices" => array('accepted' => 'accepted', 'pending' => 'pending')))
          //->add('createdAt')
          //->add('acceptAt')
          //->add('organization', array('type' => new OrganizationType()))
          //->add('entitlementPack', array('type' => new ServiceType()))
          ->add(
            'organization',
            'entity',
            array(
              'class'    => 'HexaaStorageBundle:Organization',
              'property' => 'id',
              'label'    => 'organization_id',
            )
          )
          ->add(
            'entitlement_pack',
            'entity',
            array(
              'class'    => 'HexaaStorageBundle:EntitlementPack',
              'property' => 'id',
              'label'    => 'entitlement_pack_id',
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
            'data_class'      => 'Hexaa\StorageBundle\Entity\OrganizationEntitlementPack',
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
