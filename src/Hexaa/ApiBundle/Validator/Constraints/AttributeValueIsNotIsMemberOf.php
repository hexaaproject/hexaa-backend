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
class AttributeValueIsNotIsMemberOf extends Constraint
{

    public $attributeSpecNotFoundMessage = 'Non-existent attribute specification id given';
    public $forbiddenAttributeValueMessage = "This is a computed attribute, can't assign values to it!";

    public function validatedBy()
    {
        return 'attribute_value_is_not_member_of';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}