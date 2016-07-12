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

class AttributeValueIsNotIsMemberOfValidator extends ConstraintValidator
{

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value->getAttributeSpec()) {
            $this->context->buildViolation($constraint->attributeSpecNotFoundMessage)
              ->addViolation();
            $this->context->buildViolation($constraint->attributeSpecNotFoundMessage)
              ->atPath("attribute_spec")
              ->addViolation();
        } else {
            /* @var $as \Hexaa\StorageBundle\Entity\AttributeSpec */
            $as = $value->getAttributeSpec();
            if ($as->getUri() == "urn:oid:1.3.6.1.4.1.5923.1.5.1.1") {
                $this->context->buildViolation($constraint->forbiddenAttributeValueMessage)
                  ->addViolation();
                $this->context->buildViolation($constraint->forbiddenAttributeValueMessage)
                  ->atPath("attribute_spec")
                  ->addViolation();
            }
        }
    }
}