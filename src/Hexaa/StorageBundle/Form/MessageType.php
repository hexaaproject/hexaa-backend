<?php

namespace Hexaa\StorageBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ExecutionContextInterface;

class MessageType extends AbstractType {

    protected $em;

    public function __construct(EntityManager $entityManager){
        $this->em = $entityManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('organization')
            ->add('role')
            ->add('service')
            ->add('target', 'text', array(
                //'choices' => array("user", "manager", "admin"),
                'constraints' => array(
                    new Choice(array(
                        "choices" => array("user", "manager", "admin"),
                        "message" => "must be one of 'user, 'manager', 'admin'"
                    )),
                    new NotBlank()
                )
            ))
            ->add('message', 'textarea', array(
                'constraints' => new NotBlank()
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'constraints'=>array(new Callback(array('methods'=>array(array($this,'validateTarget')))))
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'message';
    }

    public function validateTarget($data, ExecutionContextInterface $context) {

        if ($data['target'] != "admin") {
            if ($data['organization'] != null) {
                if ($data['service'] != null) {
                    $context->addViolation('Exactly one of service or organization must be given!');
                } else {
                    $o = $this->em->getRepository('HexaaStorageBundle:Organization')->find($data['organization']);
                    if ($data['role'] != null) {
                        $r = $this->em->getRepository('HexaaStorageBundle:Role')->find($data['role']);
                        if ($r == null) {
                            $context->addViolationAt('role', 'Invalid role specified.');
                        } else {
                            // Have existing organization and role
                            $this->checkTargetIfNotAdmin($data['target'], $context, $o, $r);
                            if ($r->getOrganization() != $o){
                                $context->addViolationAt("role", "Role specified is not a role of the given organization.");
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
                        $context->addViolationAt('role', 'Role can not be defined with service and without a valid organization.');
                    } else {
                        // Have existing service
                        $s = $this->em->getRepository('HexaaStorageBundle:Service')->find($data['service']);
                        $this->checkTargetIfNotAdmin($data['target'], $context, null, null, $s);
                    }
                }
            }
        } else {
            if ($data['organization']!=null){
                $context->addViolationAt('organization', "Organization must be empty if target is admin");
            }
            if ($data['role']!=null){
                $context->addViolationAt('role', "Role must be empty if target is admin");
            }
            if ($data['service']!=null){
                $context->addViolationAt('service', "Service must be empty if target is admin");
            }
        }
    }

    private function checkTargetIfNotAdmin($target, ExecutionContextInterface $context, $organization = null, $role = null, $service = null){
        switch ($target) {
            case "user":
                if ($organization == null){
                    $context->addViolationAt('target', "Target can't be 'user' if no organization is given.");
                }
                if ($service != null){
                    $context->addViolationAt('target', "Target can't be 'user' if service is given.");
                }
                break;
            case "manager":
                if ($role!=null){
                    $context->addViolationAt('target', "Target can't be 'manager' if role is given.");
                }
                break;
        }
    }
}
