<?php
/**
 * Created by solazs
 * Date: 8/19/15
 * Time: 10:54 AM
 */

namespace Hexaa\ApiBundle\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\AnnotationException;


/**
 * @Annotation
 * @Target("METHOD")
 */
class InvokeHook {
    private $types;

    public function __construct(array $types = array()) {
        $this->types = $types;
        if (!array_key_exists("value", $this->types) || $this->types["value"] == null) {
            throw new AnnotationException("Empty value is not permitted for InvokeHook annotation");
        }
    }

    public function getTypes() {
        if (!array_key_exists("value", $this->types) || !is_array($this->types["value"])) {
            $this->types["value"] = array($this->types["value"]);
        }

        return $this->types["value"];
    }

}