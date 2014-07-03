<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UrlInvitationType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('emails')
            ->add('url')
            //->add('status')
            ->add('landing_url')
            ->add('do_redirect')
            ->add('as_manager')
            ->add('message')
            //->add('counter')
            //->add('createdAt')
            ->add('start_date')
            ->add('end_date')
            ->add('limit')
                ->add('role', 'entity', array(
                    'class' => 'HexaaStorageBundle:Role',
                    'property' => 'id',
                    'label' => 'role_id'
                ))
                ->add('organization', 'entity', array(
                    'class' => 'HexaaStorageBundle:Organization',
                    'property' => 'id',
                    'label' => 'organization_id'
                ))
                ->add('service', 'entity', array(
                    'class' => 'HexaaStorageBundle:Service',
                    'property' => 'id',
                    'label' => 'service_id'
                ))
            //->add('inviter')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hexaa\StorageBundle\Entity\UrlInvitation'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }
}
