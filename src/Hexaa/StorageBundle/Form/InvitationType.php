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
            'collection',
            array('type' => 'email', "allow_delete" => true, "allow_add" => true, "delete_empty" => true)
          )
          ->add('landing_url')
          ->add('do_redirect', "checkbox")
          ->add('as_manager', "checkbox")
          ->add('message')
          ->add('locale')
          //->add('counter')
          //->add('created_at')
          //->add('accept_at')
          //->add('lastReinvite_at')
          ->add('start_date', 'date', array('widget' => 'single_text'))
          ->add('end_date', 'date', array('widget' => 'single_text'))
          ->add('limit')
          ->add(
            'role',
            'entity',
            array(
              'class'    => 'HexaaStorageBundle:Role',
              'property' => 'id',
              'label'    => 'role_id',
            )
          )
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
            'service',
            'entity',
            array(
              'class'    => 'HexaaStorageBundle:Service',
              'property' => 'id',
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
