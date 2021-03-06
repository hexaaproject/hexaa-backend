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

namespace Hexaa\ApiBundle\Handler;


use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Entity\Entitlement;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;

class AttributeCacheHandler
{
    protected $cache;
    protected $em;
    protected $cacheId;
    protected $hints;


    function __construct(EntityManager $em, Cache $cache)
    {
        $this->cache = $cache;
        $this->em = $em;
        $this->cacheId = 'attribute_data';
        $this->hints = null;
    }

    function getData()
    {
        if ($data = $this->cache->fetch($this->cacheId)) {
            return $data;
        } else {
            $data = $this->computeData();
            $this->cache->save($this->cacheId, $data);

            return $data;
        }
    }

    public function computeData()
    {
        $computedData = array();
        // Query all principals of all (or hinted) sercvices and all of their releaseable attributes
        if ($this->hints !== null) {
            if (count($this->hints) == 0) {
                $ss = array();
            } else {
                $ss = $this->em->createQueryBuilder()
                  ->select('service')
                  ->from('HexaaStorageBundle:Service', 'service')
                  ->where('service.isEnabled = 1')
                  ->andWhere('service.id IN (:sids)')
                  ->setParameter(':sids', $this->hints)
                  ->getQuery()
                  ->getResult();
            }
        } else {
            $ss = $this->em->getRepository("HexaaStorageBundle:Service")->findBy(array('isEnabled' => true));
        }
        $avps = array();
        /* @var $s \Hexaa\StorageBundle\Entity\Service */
        foreach ($ss as $s) {
            $sData = array();

            /*
             * find all related principals
             */

            // from entitlement only
            $principals = $this->em->getRepository('HexaaStorageBundle:Principal')->findAllByRelatedService($s);

            $asIds = array();

            // Get attribute spec - service connectors
            $ass = $this->em->createQueryBuilder()
              ->select("attrspec.id")
              ->from('HexaaStorageBundle:AttributeSpec', 'attrspec')
              ->innerJoin(
                'HexaaStorageBundle:ServiceAttributeSpec',
                'sas',
                'WITH',
                'sas.attributeSpec = attrspec'
              )
              ->where("sas.service = :s")
              ->setParameters(array("s" => $s))
              ->getQuery()
              ->getArrayResult();
            foreach ($ass as $as) {
                $asIds[] = $as['id'];
            }

            $ps = $this->em->createQueryBuilder()
              ->select('p')
              ->from('HexaaStorageBundle:Principal', 'p')
              ->innerJoin('HexaaStorageBundle:AttributeValuePrincipal', 'avp', 'WITH', 'p = avp.principal')
              ->innerJoin('HexaaStorageBundle:AttributeSpec', 'attrspec', 'WITH', 'avp.attributeSpec = attrspec')
              ->where('attrspec.id in (:attids)')
              ->setParameter(':attids', $asIds)
              ->getQuery()
              ->getResult();

            foreach ($ps as $p) {
                if (!in_array($p, $principals, true)) {
                    $principals[] = $p;
                }
            }

            $ps = $this->em->createQueryBuilder()
              ->select('p')
              ->from('HexaaStorageBundle:Principal', 'p')
              ->innerJoin('HexaaStorageBundle:Organization', 'o', 'WITH', 'p MEMBER OF o.principals')
              ->innerJoin('HexaaStorageBundle:AttributeValueOrganization', 'avo', 'WITH', 'o = avo.organization')
              ->innerJoin('HexaaStorageBundle:AttributeSpec', 'attrspec', 'WITH', 'avo.attributeSpec = attrspec')
              ->where('attrspec.id in (:attids)')
              ->setParameter(':attids', $asIds)
              ->getQuery()
              ->getResult();

            foreach ($ps as $p) {
                if (!in_array($p, $principals, true)) {
                    $principals[] = $p;
                }
            }

            foreach ($principals as $p) {
                $retarr = array();

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
                    if (!array_key_exists($avp->getAttributeSpec()->getUri(), $retarr)) {
                        $retarr[$avp->getAttributeSpec()->getUri()] = array();
                    }
                    if (!in_array($avp->getValue(), $retarr[$avp->getAttributeSpec()->getUri()])) {
                        array_push($retarr[$avp->getAttributeSpec()->getUri()], $avp->getValue());
                    }
                }

                // Get the values by organization
                $avos = $this->em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->findAll();
                /* @var $avo \Hexaa\StorageBundle\Entity\AttributeValueOrganization */
                foreach ($avos as $avo) {
                    if ($avo->hasService($s) || ($avo->getServices()->count() == 0)) {
                        if (!array_key_exists($avo->getAttributeSpec()->getUri(), $retarr)) {
                            $retarr[$avo->getAttributeSpec()->getUri()] = array();
                        }
                        if (!in_array($avo->getValue(), $retarr[$avo->getAttributeSpec()->getUri()])) {
                            array_push($retarr[$avo->getAttributeSpec()->getUri()], $avo->getValue());
                        }
                    }
                    $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')
                      ->findAllByPrincipalAndService($p, $s);

                    if ((!isset($retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'])
                        || !is_array($retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7']))
                      && count($es) > 0
                    ) {
                        $retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'] = array();
                        $attrNames[] = 'eduPersonEntitlement';
                    }
                    /* @var $e Entitlement */
                    foreach ($es as $e) {
                        $retarr['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'][] = $e->getUri();
                    }
                }
                $sData[$p->getFedid()] = $retarr;
            }
            $computedData[$s->getId()] = $sData;
        }

        return $computedData;
    }

    function isUpToDate()
    {
        // Might need some better heuristics
        return $this->computeData() === $this->cache->fetch($this->cacheId);
    }

    function updateData()
    {
        $this->cache->save($this->cacheId, $this->computeData());
    }

    function setCacheId($cacheId)
    {
        $this->cacheId = $cacheId.'_attribute_data';
    }

    function setHints($hints)
    {
        $this->hints = $hints;
    }
}