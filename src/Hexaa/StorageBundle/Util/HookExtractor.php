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

namespace Hexaa\StorageBundle\Util;


use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Entity\Hook;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;
use Monolog\Logger;

class HookExtractor
{
    /* @var $em \Doctrine\ORM\EntityManager */
    protected $em;
    protected $hookLog;
    protected $releaseLog;
    protected $cache;

    public function __construct($em, Logger $hookLog, Logger $releaseLog, Cache $cache)
    {
        $this->loglbl = "[HookExtractor] ";
        $this->em = $em;
        $this->hookLog = $hookLog;
        $this->releaseLog = $releaseLog;
        $this->cache = $cache;
    }

    public function extractAll($cacheId)
    {
        $this->loglbl = $this->loglbl.$cacheId." ";
        if ($hooksData = $this->cache->fetch($cacheId)) {
            $hooksToDispatch = array();
            foreach ($hooksData as $hookData) {
                $hooksToDispatch[] = $this->extract($hookData, $cacheId);
            }
            $this->cache->delete($cacheId);
            $this->cache->delete($cacheId.'_attribute_data');

            return $hooksToDispatch;
        } else {
            $this->hookLog->error($this->loglbl."No cache hit!");

            return null;
        }

    }

    public function extract($options, $cacheId)
    {
        $this->hookLog->debug($this->loglbl."Extracting ".$options['type']);
        switch ($options['type']) {
            case "attribute_change":
                return $this->extractAttributeChange($options, $cacheId);
                break;
            case "user_removed":
                return $this->extractUserRemoved($options, $cacheId);
                break;
            case "user_added":
                return $this->extractUserAdded($options, $cacheId);
                break;
            default:
                return array();
        }
    }

    protected function extractAttributeChange($options, $cacheId)
    {
        $oldData = $options['oldData'];
        $data = $this->cache->fetch($cacheId.'_attribute_data');
        $diff = $this->array_diff_assoc_recursive($data, $oldData);
        $diff2 = $this->array_diff_assoc_recursive($oldData, $data);

        $sids = array();
        foreach (array_keys($diff) as $sid) {
            $sids[] = $sid;
        }

        foreach (array_keys($diff2) as $sid) {
            if (!in_array($sid, $sids)) {
                $sids[] = $sid;
            }
        }


        $hs = $this->em->createQueryBuilder()
          ->select('h')
          ->from('HexaaStorageBundle:Hook', 'h')
          ->innerJoin('h.service', 's')
          ->where("h.type = 'attribute_change'")
          ->andWhere('s.id in (:sids)')
          ->andWhere('s.isEnabled = true')
          ->setParameter(':sids', $sids)
          ->getQuery()
          ->getResult();

        $avps = array();
        $retarr = array();

        /* @var $hook Hook */
        foreach ($hs as $hook) {
            // Get attributes for service
            $hookStuff = array('hook' => $hook, 'content' => array());
            $s = $hook->getService();

            $oldFedids = array();
            if (array_key_exists($s->getId(), $oldData)) {
                $oldFedids = array_keys($oldData[$s->getId()]);
            }
            $newFedids = array();
            if (array_key_exists($s->getId(), $data)) {
                $newFedids = array_keys($data[$s->getId()]);
            }

            $fedids = array_unique(array_merge($oldFedids, $newFedids));

            /* @var $principals ArrayCollection */
            $principals = $this->em->createQueryBuilder()
              ->select('p')
              ->from('HexaaStorageBundle:Principal', 'p')
              ->where('p.fedid in (:fedids)')
              ->setParameter(':fedids', $fedids)
              ->getQuery()
              ->getResult();

            /* @var $p Principal */
            foreach ($principals as $p) {
                $attributes = array();
                $attrNames = array();

                // Get attribute spec - service connectors
                $sass = $this->em->createQueryBuilder()
                  ->select("sas")
                  ->from('HexaaStorageBundle:ServiceAttributeSpec', 'sas')
                  ->where("sas.service = :s")
                  ->setParameters(array("s" => $s))
                  ->getQuery()
                  ->getResult();

                //  Get the values by principal
                /* @var $sas ServiceAttributeSpec */
                foreach ($sass as $sas) {
                    $tmps = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findBy(
                      array(
                        "attributeSpec" => $sas->getAttributeSpec(),
                        "principal"     => $p,
                      )
                    );
                    /* @var $tmp AttributeValuePrincipal */
                    foreach ($tmps as $tmp) {
                        if ($tmp->hasService($s) || ($tmp->getServices()->count() == 0)) {
                            $avps[] = $tmp;
                        }
                    }
                }
                // Place the attributes in the return array
                /* @var $avp AttributeValuePrincipal */
                foreach ($avps as $avp) {
                    $attributes[$avp->getAttributeSpec()->getUri()] = array();
                    if (!in_array($avp->getAttributeSpec()->getName(), $attrNames)) {
                        $attrNames[] = $avp->getAttributeSpec()->getName();
                    }
                }

                /* @var $avp AttributeValuePrincipal */
                foreach ($avps as $avp) {
                    if (!in_array($avp->getValue(), $attributes[$avp->getAttributeSpec()->getUri()])) {
                        array_push($attributes[$avp->getAttributeSpec()->getUri()], $avp->getValue());
                    }
                }

                // Get the values by organization
                $avos = $this->em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->findAll();
                /* @var $avo \Hexaa\StorageBundle\Entity\AttributeValueOrganization */
                foreach ($avos as $avo) {
                    if ($avo->hasService($s) || ($avo->getServices()->count() == 0)) {
                        if (!array_key_exists($avo->getAttributeSpec()->getUri(), $attributes)) {
                            $attributes[$avo->getAttributeSpec()->getUri()] = array();
                        }

                        if (!in_array($avo->getAttributeSpec()->getName(), $attrNames)) {
                            $attrNames[] = $avo->getAttributeSpec()->getName();
                        }
                        if (!in_array($avo->getValue(), $attributes[$avo->getAttributeSpec()->getUri()])) {
                            array_push($attributes[$avo->getAttributeSpec()->getUri()], $avo->getValue());
                        }
                    }
                }
                $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipalAndService(
                  $p,
                  $s
                );

                if ((!isset($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'])
                    || !is_array($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.7']))
                  && count($es) > 0
                ) {
                    $attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'] = array();
                    $attrNames[] = 'eduPersonEntitlement';
                }
                foreach ($es as $e) {
                    $attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'][] = $e->getUri();
                }

                if (count($attributes) != 0) {
                    $hookStuff['content'][$p->getFedid()] = $attributes;
                }

                $releasedAttributes = "";
                foreach ($attrNames as $attrName) {
                    $releasedAttributes = $releasedAttributes." ".$attrName.", ";
                }
                $releasedAttributes = substr($releasedAttributes, 0, strlen($releasedAttributes) - 2);
                $this->releaseLog->info(
                  "[attribute release] released attributes [".$releasedAttributes
                  ." ] of user with fedid=".$p->getFedid()." to service with entityid=".$s->getEntityid()
                );

                //Create News object to notify the user
                $n = new News();
                $n->setPrincipal($p);
                $n->setService($s);
                $n->setTitle("Attribute release");
                $n->setMessage(
                  "We have released some attributes (".$releasedAttributes." ) of ".$n->getPrincipal()->getFedid(
                  )." to service ".$s->getName()
                );
                $n->setTag("attribute_release");
                $this->em->persist($n);
                $this->em->flush();

            }
            $retarr[] = $hookStuff;
        }
        $this->hookLog->debug($this->loglbl.'Extracted '.$options['type'].', returning '.count($retarr).' items.');

        return $retarr;
    }

    /**
     * from http://php.net/manual/en/function.array-diff-assoc.php#111675
     * Calculates recursive difference of two arrays
     *
     * @param $array1
     * @param $array2
     * @return array
     */
    function array_diff_assoc_recursive($array1, $array2)
    {
        $difference = array();
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->array_diff_assoc_recursive($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } else {
                if (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                    $difference[$key] = $value;
                }
            }
        }

        return $difference;
    }

    protected function extractUserRemoved($options, $cacheId)
    {
        $oldData = $options['oldData'];
        $data = $this->cache->fetch($cacheId.'_attribute_data');
        $diff = $this->array_diff_assoc_recursive($oldData, $data);

        $hs = $this->em->createQueryBuilder()
          ->select('h')
          ->from('HexaaStorageBundle:Hook', 'h')
          ->innerJoin('h.service', 's')
          ->where("h.type = 'user_removed'")
          ->andWhere('s.isEnabled = true')
          ->andWhere('s.id in (:sids)')
          ->setParameter(':sids', array_keys($diff))
          ->getQuery()
          ->getResult();

        $retarr = array();

        /* @var $hook Hook */
        foreach ($hs as $hook) {
            $hookStuff = array('hook' => $hook, 'content' => array());

            $oldFedids = array();
            if (array_key_exists($hook->getServiceId(), $oldData)) {
                $oldFedids = array_keys($oldData[$hook->getServiceId()]);
            }
            $newFedids = array();
            if (array_key_exists($hook->getServiceId(), $data)) {
                $newFedids = array_keys($data[$hook->getServiceId()]);
            }

            $fedids = $this->array_diff_assoc_non_string_compare($oldFedids, $newFedids);

            $hookStuff["content"] = $fedids;

            $retarr[] = $hookStuff;
        }
        $this->hookLog->debug($this->loglbl.'Extracted '.$options['type'].', returning '.count($retarr).' items.');

        return $retarr;
    }

    protected function array_diff_assoc_non_string_compare($array1, $array2)
    {
        $retarr = array();
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $retarr[$key] = $value;
            } else {
                if (is_array($value)) {
                    if (!is_array($array2[$key])) {
                        $retarr[$key] = $value;
                    } elseif (serialize($array1[$key]) !== serialize($array2[$key])) {
                        $retarr[$key] = $value;
                    }
                } else {
                    if ($array1[$key] !== $array2[$key]) {
                        $retarr[$key] = $array1[$key];
                    }
                }
            }
        }

        return $retarr;
    }

    protected function extractUserAdded($options, $cacheId)
    {
        $oldData = $options['oldData'];
        $data = $this->cache->fetch($cacheId.'_attribute_data');
        $diff = $this->array_diff_assoc_recursive($data, $oldData);

        $hs = $this->em->createQueryBuilder()
          ->select('h')
          ->from('HexaaStorageBundle:Hook', 'h')
          ->innerJoin('h.service', 's')
          ->where("h.type = 'user_added'")
          ->andWhere('s.id in (:sids)')
          ->andWhere('s.isEnabled = true')
          ->setParameter(':sids', array_keys($diff))
          ->getQuery()
          ->getResult();

        $retarr = array();

        /* @var $hook Hook */
        foreach ($hs as $hook) {
            $hookStuff = array('hook' => $hook, 'content' => array());

            $oldFedids = array();
            if (array_key_exists($hook->getServiceId(), $oldData)) {
                $oldFedids = array_keys($oldData[$hook->getServiceId()]);
            }
            $newFedids = array();
            if (array_key_exists($hook->getServiceId(), $data)) {
                $newFedids = array_keys($data[$hook->getServiceId()]);
            }

            $fedids = $this->array_diff_assoc_non_string_compare($newFedids, $oldFedids);

            $content = array();
            foreach ($fedids as $fedid) {
                $content[$fedid] = $diff[$hook->getServiceId()][$fedid];
            }

            $hookStuff["content"] = $content;

            if (count($content) > 0) {
                $retarr[] = $hookStuff;
            }
        }
        $this->hookLog->debug($this->loglbl.'Extracted '.$options['type'].', returning '.count($retarr).' items.');

        return $retarr;
    }
}
