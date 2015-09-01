<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 8/31/15
 * Time: 3:19 PM
 */

namespace Hexaa\StorageBundle\Util;


class HookExtractor {
    protected $em;

    public function __construct($em) {
        $this->em = $em;
    }

    public function extract($options) {
        switch($options['type']) {
            case "attribute_change":
                return $this->extractAttributeChange($options['_attributeChangeAffectedEntity']);
                break;
            default:
                return array();
        }
    }

    protected function extractAttributeChange($affectedEntity) {
        switch($affectedEntity['entity']) {
            case "Principal":
                $affectedEntity['id']; // array that stores the principal ids
                break;
            case "Entitlement":

                break;
        }

        return array();
    }
}