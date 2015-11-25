<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 8/31/15
 * Time: 3:19 PM
 */

namespace Hexaa\StorageBundle\Util;


use Doctrine\Common\Cache\Cache;
use Hexaa\StorageBundle\Entity\AttributeValuePrincipal;
use Hexaa\StorageBundle\Entity\Consent;
use Hexaa\StorageBundle\Entity\Hook;
use Hexaa\StorageBundle\Entity\News;
use Hexaa\StorageBundle\Entity\ServiceAttributeSpec;
use Monolog\Logger;

class HookExtractor
{
    /* @var $em \Doctrine\ORM\EntityManager */
    protected $em;
    protected $hexaa_consent_module;
    protected $releaseLog;
    protected $cache;

    public function __construct($em, $hexaa_consent_module, Logger $releaseLog, Cache $cache)
    {
        $this->em = $em;
        $this->hexaa_consent_module = $hexaa_consent_module;
        $this->releaseLog = $releaseLog;
        $this->cache = $cache;
    }

    public function extractAll($cacheId)
    {
        if ($hooksData = $this->cache->fetch($cacheId)) {
            $hooksToDispatch = array();
            foreach ($hooksData as $hookData) {
                $hooksToDispatch[] = $this->extract($hookData);
            }

            return $hooksToDispatch;
        } else {
            return null;
        }

    }

    public function extract($options)
    {
        switch ($options['type']) {
            case "attribute_change":
                return $this->extractAttributeChange($options);
                break;
            case "user_removed":
                return $this->extractUserRemoved($options);
                break;
            case "user_added":
                return $this->extractUserAdded($options);
                break;
            default:
                return array();
        }
    }

    protected function extractAttributeChange($options)
    {
        $oldData = $options['oldData'];
        $data = $this->cache->fetch('attribute_data');
        $diff = $this->array_diff_assoc_recursive($data, $oldData);

        $hs = $this->em->createQueryBuilder()
            ->select('h')
            ->from('HexaaStorageBundle:Hook', 'h')
            ->innerJoin('h.service', 's')
            ->where("h.type = 'attribute_change'")
            ->andWhere('s.id in (:sids)')
            ->andWhere('s.isEnabled = true')
            ->setParameter(':sid', array_keys($diff));

        $avps = array();
        $retarr = array();
        $attrNames = array();

        /* @var $hook Hook */
        foreach ($hs as $hook) {
            // Get attributes for service
            $hookStuff = array('hook' => $hook, 'content' => array());
            $s = $hook->getService();

            $oldFedids = array_keys($oldData[$s->getId()]);
            $newFedids = array_keys($data[$s->getId()]);

            $allFedids = array_merge($oldFedids, $newFedids);
            $fedids = array();

            foreach ($allFedids as $fedid) {
                if (in_array($fedid, $oldFedids) && in_array($fedid, $newFedids)) {
                    $fedids[] = $fedid;
                }
            }

            $principals = $this->em->createQueryBuilder()
                ->select('p')
                ->from('HexaaStorageBundle:Principal', 'p')
                ->where('fedid in (:fedids)')
                ->setParameter(':fedids', $fedids)
                ->getQuery()
                ->getResult();


            foreach ($principals as $p) {

                $attributes = array();

                // Get Consent object, or create it if it doesn't exist
                $c = $this->em->getRepository('HexaaStorageBundle:Consent')->findOneBy(array(
                    "principal" => $p,
                    "service"   => $s
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
                    if ($this->hexaa_consent_module == false || $this->hexaa_consent_module == "false") {
                        $releaseAttributeSpec = true;
                    }
                    if ($releaseAttributeSpec) {
                        $tmps = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->findBy(
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
                if ($this->hexaa_consent_module == false || $this->hexaa_consent_module == "false") {
                    $releaseEntitlements = true;
                }
                if ($releaseEntitlements) {
                    $es = $this->em->getRepository('HexaaStorageBundle:Entitlement')->findAllByPrincipalAndService($p,
                        $s);

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
                }

                if (count($attributes) != 0) {
                    $hookStuff['content'][$p->getFedid()] = $attributes;
                }

                $releasedAttributes = "";
                foreach ($attrNames as $attrName) {
                    $releasedAttributes = $releasedAttributes . " " . $attrName . ", ";
                }
                $releasedAttributes = substr($releasedAttributes, 0, strlen($releasedAttributes) - 2);
                $this->releaseLog->info("[attribute release] released attributes [" . $releasedAttributes
                    . " ] of user with fedid=" . $p->getFedid() . " to service with entityid=" . $s->getEntityid());

                //Create News object to notify the user
                $n = new News();
                $n->setPrincipal($p);
                $n->setService($s);
                $n->setTitle("Attribute release");
                $n->setMessage("We have released some attributes (" . $releasedAttributes . " ) of " . $n->getPrincipal()->getFedid() . " to service " . $s->getName());
                $n->setTag("attribute_release");
                $this->em->persist($n);
                $this->em->flush();

            }
            $retarr[] = $hookStuff;
        }


        return $retarr;
    }

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

    protected function extractUserRemoved($options)
    {
        $oldData = $options['oldData'];
        $data = $this->cache->fetch('attribute_data');
        $diff = array_diff_assoc($oldData, $data);

        $hs = $this->em->createQueryBuilder()
            ->select('h')
            ->from('HexaaStorageBundle:Hook', 'h')
            ->innerJoin('h.service', 's')
            ->where("h.type = 'user_removed'")
            ->andWhere('s.id in (:sids)')
            ->andWhere('s.isEnabled = true')
            ->setParameter(':sid', array_keys($diff));

        $retarr = array();

        /* @var $hook Hook */
        foreach ($hs as $hook) {
            $hookStuff = array('hook' => $hook, 'content' => array());

            $hookStuff["content"] = array_keys(array_diff_assoc(
                $oldData[$hook->getServiceId()],
                $data[$hook->getServiceId()]
            ));

            $retarr[] = $hookStuff;
        }

        return $retarr;
    }

    /*
     * from http://php.net/manual/en/function.array-diff-assoc.php#111675
     */

    protected function extractUserAdded($options)
    {
        $oldData = $options['oldData'];
        $data = $this->cache->fetch('attribute_data');
        $diff = $this->array_diff_assoc_recursive($data, $oldData);

        $hs = $this->em->createQueryBuilder()
            ->select('h')
            ->from('HexaaStorageBundle:Hook', 'h')
            ->innerJoin('h.service', 's')
            ->where("h.type = 'user_added'")
            ->andWhere('s.id in (:sids)')
            ->andWhere('s.isEnabled = true')
            ->setParameter(':sid', array_keys($diff));

        $retarr = array();

        /* @var $hook Hook */
        foreach ($hs as $hook) {
            $hookStuff = array('hook' => $hook, 'content' => array());

            $hookStuff["content"] = $diff[$hook->getServiceId()];

            $retarr[] = $hookStuff;
        }

        return $retarr;
    }
}