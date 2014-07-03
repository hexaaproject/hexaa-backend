<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmailInvitationType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('email')
                //->add('status')
                ->add('landing_url')
                ->add('do_redirect')
                ->add('as_manager')
                ->add('message')
                //->add('counter')
                //->add('created_at')
                //->add('accept_at')
                //->add('lastReinvite_at')
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
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Hexaa\StorageBundle\Entity\EmailInvitation'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return '';
    }

}
