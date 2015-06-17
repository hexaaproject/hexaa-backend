<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 6/16/15
 * Time: 2:33 PM
 */

namespace Hexaa\ApiBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AttributeValueHasNoServiceIfNotMultivalue extends Constraint {

    public $attributeSpecNotFoundMessage = 'Non-existent attribute specification id given';
    public $attributeSpecIsSingleValueMessage = "Can't assign services to a non-multivalue attribute";

    public function validatedBy() {
        return 'attribute_has_no_service_if_not_multivalue';
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }
}