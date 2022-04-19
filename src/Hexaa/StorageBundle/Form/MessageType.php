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

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class MessageType extends AbstractType
{

    protected $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('organization')
          ->add('role')
          ->add('service')
          ->add(
            'target',
            TextType::class,
            array(
              'constraints' => array(
                new Choice(
                  array(
                    "choices" => array("user", "manager", "admin"),
                    "message" => "must be one of 'user, 'manager', 'admin'",
                  )
                ),
                new NotBlank(),
              ),
            )
          )
          ->add(
            'subject',
            TextType::class,
            array(
              'constraints' => new NotBlank(),
            )
          )
          ->add(
            'message',
            TextareaType::class,
            array(
              'constraints' => new NotBlank(),
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
            'csrf_protection' => false,
            'constraints'     => array(new Callback(array('methods' => array(array($this, 'validateTarget'))))),
          )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'message';
    }

    public function validateTarget($data, ExecutionContextInterface $context)
    {

        if ($data['target'] != "admin") {
            if ($data['organization'] != null) {
                if ($data['service'] != null) {
                    $context->addViolation('Exactly one of service or organization must be given!');
                } else {
                    $o = $this->em->getRepository('HexaaStorageBundle:Organization')->find($data['organization']);
                    if ($data['role'] != null) {
                        $r = $this->em->getRepository('HexaaStorageBundle:Role')->find($data['role']);
                        if ($r == null) {
                            $context->buildViolation('Invalid role specified.')
                              ->atPath('role')
                              ->addViolation();
                        } else {
                            // Have existing organization and role
                            $this->checkTargetIfNotAdmin($data['target'], $context, $o, $r);
                            if ($r->getOrganization() != $o) {
                                $context->buildViolation('Role specified is not a role of the given organization.')
                                  ->atPath('role')
                                  ->addViolation();
                            }
                        }
                    } else {
                        // Have existing organization and role is empty
                        $this->checkTargetIfNotAdmin($data['target'], $context, $o);
                    }
                }
            } else {
                if ($data['service'] == null) {
                    $context->addViolation('No organization nor service id given.');
                } else {
                    if ($data['role'] != null) {
                        $context->buildViolation('Role can not be defined with service and without a valid organization.')
                          ->atPath('role')
                          ->addViolation();
                    } else {
                        // Have existing service
                        $s = $this->em->getRepository('HexaaStorageBundle:Service')->find($data['service']);
                        $this->checkTargetIfNotAdmin($data['target'], $context, null, null, $s);
                    }
                }
            }
        } else {
            if ($data['organization'] != null) {
                $context->buildViolation('Organization must be empty if target is admin')
                  ->atPath('organization')
                  ->addViolation();
            }
            if ($data['role'] != null) {
                $context->buildViolation('Role must be empty if target is admin')
                  ->atPath('role')
                  ->addViolation();
            }
            if ($data['service'] != null) {
                $context->buildViolation('Service must be empty if target is admin')
                  ->atPath('service')
                  ->addViolation();
            }
        }
    }

    private function checkTargetIfNotAdmin(
      $target,
      ExecutionContextInterface $context,
      $organization = null,
      $role = null,
      $service = null
    ) {
        switch ($target) {
            case "user":
                if ($organization == null) {
                    $context->buildViolation("Target can't be 'user' if no organization is given.")
                      ->atPath('target')
                      ->addViolation();
                }
                if ($service != null) {
                    $context->buildViolation("Target can't be 'user' if service is given.")
                      ->atPath('target')
                      ->addViolation();
                }
                break;
            case "manager":
                if ($role != null) {
                    $context->buildViolation("Target can't be 'manager' if role is given.")
                      ->atPath('target')
                      ->addViolation();
                }
                break;
        }
    }
}
