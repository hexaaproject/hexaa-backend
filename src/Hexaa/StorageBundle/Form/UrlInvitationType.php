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
            ->add('status')
            ->add('landingUrl')
            ->add('doRedirect')
            ->add('asManager')
            ->add('message')
            ->add('counter')
            ->add('createdAt')
            ->add('startDate')
            ->add('endDate')
            ->add('limit')
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
            'data_class' => 'Hexaa\StorageBundle\Entity\UrlInvitation'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hexaa_storagebundle_urlinvitation';
    }
}
