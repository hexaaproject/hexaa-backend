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

namespace Hexaa\ApiBundle\Validator\Constraints;

use Hexaa\StorageBundle\Entity\Service;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SPContactMail extends Constraint
{

    public $invalidMessage = '%surName% is an invalid contact for the entityID %entityid%';
    protected $service;

    public function __construct($options)
    {
        if ($options['service'] and $options['service'] instanceof Service) {
            $this->service = $options['service'];
        } else {
            throw new MissingOptionsException("No service parameter given!");
        }
    }

    public function getService()
    {
        return $this->service;
    }

    public function validatedBy()
    {
        return 'sp_contact_mail';
    }
}

?>