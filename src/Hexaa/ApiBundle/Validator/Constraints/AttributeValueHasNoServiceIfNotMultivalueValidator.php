<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 6/16/15
 * Time: 2:41 PM
 */

namespace Hexaa\ApiBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AttributeValueHasNoServiceIfNotMultivalueValidator extends ConstraintValidator {

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint) {
        // Check if AttributeSpec and Service exists, throw error otherwise
        $ss = $value->getServices();
        /* @var $as \Hexaa\StorageBundle\Entity\AttributeSpec */
        $as = $value->getAttributeSpec();

        if (!$value->getAttributeSpec()) {
            $this->context->addViolation($constraint->attributeSpecNotFoundMessage);
            $this->context->addViolationAt('attribute_spec', $constraint->attributeSpecNotFoundMessage);
        } else {
            if ((!$as->getIsMultivalue()) && ($ss->count() != 0)) {
                $this->context->addViolation($constraint->attributeSpecIsSingleValueMessage);
                $this->context->addViolationAt('attribute_spec', $constraint->attributeSpecIsSingleValueMessage);
            }
        }
    }
}