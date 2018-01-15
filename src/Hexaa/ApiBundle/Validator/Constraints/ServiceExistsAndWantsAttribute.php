<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ServiceExistsAndWantsAttribute extends Constraint
{

    public $notWantedMessage = 'Service with id=%sid% does not want AttributeSpec with id=%asid%';
    public $serviceNotFoundMessage = 'Non-existent Service id given';
    public $attributeSpecNotFoundMessage = 'Non-existent attribute specification id given';
    public $attributeSpecIsSingleValueMessage = "Can't add more than one values to a non-multivalue attribute";
    public $attributeSpecMaintainerMismatchPrincipal = "Can't assign maintainer to manager to AttributeSpec with an AttributeValuePrincipal already assigned.";
    public $attributeSpecMaintainerMismatchOrganization = "Can't assign maintainer to user to AttributeSpec with an AttributeValueOrganization already assigned.";

    public function validatedBy()
    {
        return 'service_exists_and_wants_attribute';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

?>