<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 11/23/15
 * Time: 2:07 PM
 */

namespace Hexaa\ApiBundle\Handler;


use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Entity\Consent;
use Hexaa\StorageBundle\Entity\Entitlement;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;

class AttributeCacheHandler
{
    protected $cache;
    protected $em;
    protected $isConsentModuleEnabled;
    private $computedData = null;


    function __construct(EntityManager $em, Cache $cache, $consentModuleEnabled)
    {
        $this->cache = $cache;
        $this->em = $em;
        $this->isConsentModuleEnabled = $consentModuleEnabled;
    }

    function getData()
    {
        if ($serializedData = $this->cache->fetch('attribute_data')) {
            return unserialize($serializedData);
        } else {
            $data = $this->computeData();
            $this->cache->save('attribute_data', serialize($data));

            return $data;
        }
    }

    private function computeData()
    {

        if ($this->computedData === null) {
            $computedData = array();
            // Query all principals of all sercvices and all of their releaseable attributes
            $ss = $this->em->getRepository("HexaaStorageBundle:Service")->findBy(array('isEnabled' => true));
            $avps = array();
            /* @var $s \Hexaa\StorageBundle\Entity\Service */
            foreach ($ss as $s) {
                $retarr = array();
                // Get Consent object, or create it if it doesn't exist
                $c = $em->getRepository('HexaaStorageBundle:Consent')->findOneBy(array(
                    "principal" => $p,
                    "service"   => $s
                ));
                if (!$c) {
                    $c = new Consent();
                    $c->setService($s);
                    $c->setPrincipal($p);
                    $em->persist($c);
                    $em->flush();
                }

                // Get attribute spec - service connectors
                $sass = $em->createQueryBuilder()
                    ->select("sas")
                    ->from('HexaaStorageBundle:ServiceAttributeSpec', 'sas')
                    ->where("sas.service = :s")
                    ->setParameters(array("s" => $s))
                    ->getQuery()
                    ->getResult();

                //  Get the values by principal
                /* @var $sas ServiceAttributeSpec */
                foreach ($sass as $sas) {
                    $releaseAttributeSpec = $c->hasEnabledAttributeSpecs($sas->getAttributeSpec());
                    if ($this->isConsentModuleEnabled == false || $this->isConsentModuleEnabled == "false") {
                        $releaseAttributeSpec = true;
                    }
                    if ($releaseAttributeSpec) {
                        $tmps = $em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findBy(
                            array(
                                "attributeSpec" => $sas->getAttributeSpec(),
                                "principal"     => $p
                            )
                        );
                        /* @var $tmp AttributeValuePrincipal */
                        foreach ($tmps as $tmp) {
                            if ($tmp->hasService($s) || ($tmp->getServices()->count() == 0)) {
                                $avps[] = $tmp;
                            }
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
                $avos = $em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->findAll();
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
                }

                // Check if we have consent to entitlement release
                $releaseEntitlements = $c->getEnableEntitlements();
                if ($this->isConsentModuleEnabled == false || $this->isConsentModuleEnabled == "false") {
                    $releaseEntitlements = true;
                }
                if ($releaseEntitlements) {
                    $es = $em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipalAndService($p, $s);

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
                $computedData[$s->getId()] = $retarr;
            }
            $this->computedData = $computedData;
        }

        return $this->computedData;
    }

    function isUpToDate()
    {
        return $this->computeData() === unserialize($this->cache->fetch('attribute_data'));
    }

    function updateData()
    {
        $this->cache->save('attribute_data', $this->computeData());
    }
}