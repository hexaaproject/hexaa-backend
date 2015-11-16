<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Hexaa\StorageBundle\Entity\Service;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SPContactMail extends Constraint {

    public $invalidMessage = '%surName% is an invalid contact for the entityID %entityid%';
    protected $service;

    public function __construct($options) {
        if ($options['service'] and $options['service'] instanceof Service) {
            $this->service = $options['service'];
        } else {
            throw new MissingOptionsException("No service parameter given!");
        }
    }

    public function getService() {
        return $this->service;
    }

    public function validatedBy() {
        return 'sp_contact_mail';
    }
}

?>