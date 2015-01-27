<?php

namespace Hexaa\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class EntitlementURIValidator extends ConstraintValidator {

    protected $uriPrefix;

    public function __construct($uriPrefix) {
        $this->uriPrefix = $uriPrefix;
    }

    public function validate($e, Constraint $constraint) {

        if (!$e) {
            $this->context->addViolation(
                    $constraint->entitlementNotFoundMessage
            );
        } else {
            if (!preg_match('/^' . $this->uriPrefix . ':' . $e->getService()->getId() . ':[a-zA-Z0-9-_:]+$/', $e->getUri())) {
                $this->context->addViolation(
                        $constraint->notValidURIMessage, array(
                    "%uri%" => $e->getUri(),
                    "%uri_prefix%" => $this->uriPrefix . ':' . $e->getService()->getId() . ":your_text_here"
                        )
                );
                $this->context->addViolationAt('uri', $constraint->notValidURIMessage, array(
                    "%uri%" => $e->getUri(),
                    "%uri_prefix%" => $this->uriPrefix . ':' . $e->getService()->getId() . ":your_text_here"
                        )
                );
            }
        }
    }

}
