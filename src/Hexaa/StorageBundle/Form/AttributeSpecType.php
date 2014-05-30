<?php

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AttributeSpecType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('oid')
            ->add('friendlyName')
            ->add('description')
            ->add('maintainer')
            ->add('datatype')
            ->add('isMultivalue')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Hexaa\StorageBundle\Entity\AttributeSpec'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'hexaa_storagebundle_attributespec';
    }
}
