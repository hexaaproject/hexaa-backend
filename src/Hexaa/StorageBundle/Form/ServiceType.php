<?php

namespace Hexaa\StorageBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('entityid')
            ->add('url')
            ->add('description')
            ->add('org_name')
            ->add('org_description')
            ->add('org_short_name')
            ->add('org_url')
            ->add('priv_url')
            ->add('priv_description')/*
            ->add('tags', 'collection', array(
                "type"    => new TagType(),
                "allow_add" => true,
                "allow_delete" => true,
                "by_reference" => false
            ))*/
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\Service',
            'csrf_protection' => false,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }

}
