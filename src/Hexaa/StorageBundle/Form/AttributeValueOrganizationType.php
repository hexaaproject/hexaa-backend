<?php

/**
 * Copyright 2014-2018 MTA SZTAKI, ugyeletes@sztaki.hu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Hexaa\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class AttributeValueOrganizationType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('value')
          ->add(
            'services',
            CollectionType::class,
            array(
              "entry_type"         => EntityType::class,
              "entry_options"      => array(
                "class"    => 'HexaaStorageBundle:Service',
                "choice_label" => 'id',
              ),
              "allow_delete" => true,
              "allow_add"    => true,
            )
          )
          ->add(
            'attribute_spec',
            EntityType::class,
            array(
              'class'    => 'HexaaStorageBundle:AttributeSpec',
              'choice_label' => 'id',
              'label'    => 'attribute_spec_id',
            )
          )
          ->add(
            'organization',
            EntityType::class,
            array(
              'class'    => 'HexaaStorageBundle:Organization',
              'choice_label' => 'id',
              'label'    => 'organization_id',
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
            'data_class'      => 'Hexaa\StorageBundle\Entity\AttributeValueOrganization',
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
