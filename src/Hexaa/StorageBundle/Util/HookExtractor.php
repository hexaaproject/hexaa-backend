<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 8/31/15
 * Time: 3:19 PM
 */

namespace Hexaa\StorageBundle\Util;


use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Entity\Consent;
use Hexaa\StorageBundle\Entity\Hook;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;

class HookExtractor
{
    /* @var $em \Doctrine\ORM\EntityManager */
    protected $em;
    protected $hexaa_consent_module;

    public function __construct($em, $hexaa_consent_module)
    {
        $this->em = $em;
        $this->hexaa_consent_module = $hexaa_consent_module;
    }

    public function extract($options)
    {
        switch ($options['type']) {
            case "attribute_change":
                return $this->extractAttributeChange($options['_attributeChangeAffectedEntity']);
                break;
            default:
                return array();
        }
    }

    protected function extractAttributeChange($affectedEntity)
    {
        if (array_key_exists('serviceId', $affectedEntity)) {
            $hs = $this->em->createQueryBuilder()
                ->select('h')
                ->from('HexaaStorageBundle:Hook', 'h')
                ->innerJoin('h.service', 's')
                ->where("h.type = 'attribute_change'")
                ->andWhere('s.id = :sid')
                ->setParameter(':sid', $affectedEntity['serviceId'])
                ->getQuery()
                ->getResult();
        } else {
            $hs = $this->em->createQueryBuilder()
                ->select('h')
                ->from('HexaaStorageBundle:Hook', 'h')
                ->innerJoin('h.service', 's')
                ->where("h.type = 'attribute_change'")
                ->getQuery()
                ->getResult();
        }
        $principals = $this->getPrincipalsFromEntity($affectedEntity);

        $avps = array();
        $retarr = array();
        $attrNames = array();

        /* @var $hook Hook */
        foreach ($hs as $hook) {
            // Get attributes for service
            if ($hook->getService()->getIsEnabled()) {
                $hookStuff = array('url' => $hook->getUrl(), 'content' => array());
                $s = $hook->getService();

                foreach ($principals as $p) {

                    $attributes = array();

                    // Get Consent object, or create it if it doesn't exist
                    $c = $this->em->getRepository('HexaaStorageBundle:Consent')->findOneBy(array(
                        "principal" => $p,
                        "service" => $s
                    ));
                    if (!$c) {
                        $c = new Consent();
                        $c->setService($s);
                        $c->setPrincipal($p);
                        $this->em->persist($c);
                        $this->em->flush();
                    }

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
                        $releaseAttributeSpec = $c->hasEnabledAttributeSpecs($sas->getAttributeSpec());
                        if ($this->hexaa_consent_module == false || $this->hexaa_consent_module == "false")
                            $releaseAttributeSpec = true;
                        if ($releaseAttributeSpec) {
                            $tmps = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findBy(
                                array(
                                    "attributeSpec" => $sas->getAttributeSpec(),
                                    "principal" => $p
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

                    // Check if we have consent to entitlement release
                    $releaseEntitlements = $c->getEnableEntitlements();
                    if ($this->hexaa_consent_module == false || $this->hexaa_consent_module == "false")
                        $releaseEntitlements = true;
                    if ($releaseEntitlements) {
                        if (!isset($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.7']) || !is_array($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'])) {
                            $attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'] = array();
                            $attrNames[] = 'eduPersonEntitlement';
                        }
                        foreach ($this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipalAndService($p, $s) as $e) {
                            $attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.7'][] = $e->getUri();
                        }
                    }

                    $hookStuff['content'][$p->getFedid()] = $attributes;

                }
            }
            $retarr[] = $hookStuff;
        }


        return $retarr;
    }

    protected function getPrincipalsFromEntity($affectedEntity)
    {
        switch ($affectedEntity['entity']) {
            case "Principal":
                $principals = $this->em->createQueryBuilder()
                    ->select('p')
                    ->from('HexaaStorageBundle:Principal', 'p')
                    ->where('p.id in :pids')
                    ->setParameter(':pids', $affectedEntity['id'])
                    ->getQuery()
                    ->getResult();
                break;
            case "Entitlement":
                $principals = $this->em->createQueryBuilder()
                    ->select('p')
                    ->from('HexaaStorageBundle:Principal', 'p')
                    ->innerJoin('HexaaStorageBundle:RolePrincipal', 'rp', "WITH", "p = rp.principal")
                    ->innerJoin('HexaaStorageBundle:Role', 'r')
                    ->innerJoin('r.entitlements', 'e')
                    ->where("e.id in :ids")
                    ->setParameter(':ids', $affectedEntity['id'])
                    ->getQuery()
                    ->getResult();
                break;
            case "Role":
                $principals = $this->em->createQueryBuilder()
                    ->select('p')
                    ->from('HexaaStorageBundle:Principal', 'p')
                    ->innerJoin('HexaaStorageBundle:RolePrincipal', 'rp', 'WITH', 'p = rp.principal')
                    ->innerJoin('rp.role', 'r')
                    ->where('r.id = :rids')
                    ->setParameter(':rids', $affectedEntity['id'])
                    ->getQuery()
                    ->getResult();
                break;
            case "Service":
                $principals = $this->em->getRepository('HexaaStorageBundle:Principal')
                    ->findAllByRelatedServiceIds($affectedEntity['id']);
                break;
            case "AttributeSpec":
                $principals = array();
                $avpPs = $this->em->createQueryBuilder()
                    ->select("p")
                    ->from('HexaaStorageBundle:Principal', 'p')
                    ->innerJoin('HexaaStorageBundle:AttributeValuePrincipal', 'avp', 'WITH', 'p = avp.principal')
                    ->innerJoin("avp.attributeSpec", "as")
                    ->where("as.id in :asIds")
                    ->setParameter(":asIds", $affectedEntity['id'])
                    ->getQuery()
                    ->getResult();
                $avoPs = $this->em->createQueryBuilder()
                    ->select("p")
                    ->from('HexaaStorageBundle:Principal', 'p')
                    ->innerJoin('HexaaStorageBundle:AttributeValueOrganization', 'avo')
                    ->innerJoin("avo.attributeSpec", "as")
                    ->where("as.id in :asIds")
                    ->andWhere("p MEMBER OF avo.principals")
                    ->setParameter(":asIds", $affectedEntity['id'])
                    ->getQuery()
                    ->getResult();
                foreach ($avpPs as $p) {
                    if (!in_array($p, $principals)) {
                        $principals[] = $p;
                    }
                }
                foreach ($avoPs as $p) {
                    if (!in_array($p, $principals)) {
                        $principals[] = $p;
                    }
                }
                // $affectedEntity["serviceId"] has the unlinked service if there is any
                break;
            default:
                $principals = array();
        }
        return $principals;
    }
}