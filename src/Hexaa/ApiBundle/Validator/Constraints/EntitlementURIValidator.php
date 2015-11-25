<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class EntitlementURIValidator extends ConstraintValidator
{

    protected $uriPrefix;

    public function __construct($uriPrefix)
    {
        $this->uriPrefix = $uriPrefix;
    }

    public function validate($e, Constraint $constraint)
    {

        if (!$e) {
            $this->context->buildViolation($constraint->entitlementNotFoundMessage)
                ->addViolation();
        } else {
            if (!preg_match('/^' . $this->uriPrefix . ':' . $e->getService()->getId() . ':[a-zA-Z0-9-_:]+$/',
                $e->getUri())
            ) {
                $this->context->buildViolation($constraint->notValidURIMessage)
                    ->setParameter("%uri%", $e->getUri())
                    ->setParameter("%uri_prefix%",
                        $this->uriPrefix . ':' . $e->getService()->getId() . ":your_text_here")
                    ->addViolation();
                $this->context->buildViolation($constraint->notValidURIMessage)
                    ->atPath("uri")
                    ->setParameter("%uri%", $e->getUri())
                    ->setParameter("%uri_prefix%",
                        $this->uriPrefix . ':' . $e->getService()->getId() . ":your_text_here")
                    ->addViolation();
            }
        }
    }

}
