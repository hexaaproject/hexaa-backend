<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Hexaa\StorageBundle\Entity\Service;

/**
 * @Annotation
 */
class SPContactMail extends Constraint {
    
    protected $service;

    public function __construct($options)
    {
        if($options['service'] and $options['service'] instanceof Service)
        {
            $this->service = $options['service'];
        }
        else
        {
            throw new MissingOptionException("No service parameter given!");
        }
    }

    public function getService()
    {
        return $this->service;
    }

    public $invalidMessage = '%surName% is an invalid contact for the entityID %entityid%';

    public function validatedBy() {
        return 'sp_contact_mail';
    }
}

?>