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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class InvitationType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add(
            'emails',
            CollectionType::class,
            array('entry_type' => EmailType::class, "allow_delete" => true, "allow_add" => true, "delete_empty" => true)
          )
          ->add('landing_url')
          ->add('do_redirect', CheckboxType::class)
          ->add('as_manager', CheckboxType::class)
          ->add('message')
          ->add('locale')
          //->add('counter')
          //->add('created_at')
          //->add('accept_at')
          //->add('lastReinvite_at')
          ->add('start_date', DateType::class, array('widget' => 'single_text'))
          ->add('end_date',   DateType::class, array('widget' => 'single_text'))
          ->add('limit')
          ->add(
            'role',
            EntityType::class,
            array(
              'class'    => 'HexaaStorageBundle:Role',
              'choice_label' => 'id',
              'label'    => 'role_id',
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
          )
          ->add(
            'service',
            EntityType::class,
            array(
              'class'    => 'HexaaStorageBundle:Service',
              'choice_label' => 'id',
              'label'    => 'service_id',
            )
          )//->add('inviter')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
          array(
            'data_class'      => 'Hexaa\StorageBundle\Entity\Invitation',
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
