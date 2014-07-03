<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmailInvitationType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            ->add('status')
            ->add('landingUrl')
            ->add('doRedirect')
            ->add('asManager')
            ->add('message')
            ->add('counter')
            ->add('createdAt')
            ->add('acceptAt')
            ->add('lastReinviteAt')
            ->add('role')
            ->add('organization')
            ->add('service')
            ->add('inviter')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hexaa\StorageBundle\Entity\EmailInvitation'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hexaa_storagebundle_emailinvitation';
    }
}
