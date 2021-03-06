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

namespace Hexaa\ApiBundle\Hook;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HookHintResolver
{
    protected $entity;
    protected $id;
    protected $source;
    protected $em;
    protected $loglbl;
    protected $tokenStorage;

    function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->loglbl = 'HookHintResolver ';
        $this->tokenStorage = $tokenStorage;
    }

    public function resolve(Request $request)
    {
        $sids = array();
        switch ($this->source) {
            case 'attributes':
                $id = $request->attributes->get($this->id);
                break;
            case 'request':
                $id = $request->request->get($this->id);
                break;
            case 'principal':
                $id = $this->tokenStorage->getToken()->getUser()->getPrincipal();
                break;
            case 'link':
                if ($request->request->has('service')) {
                    $this->id = 'id';
                    $this->source = 'attributes';
                    $this->entity = 'Service';
                    $id = $request->request->get('service');
                } else {
                    if ($request->request->has('organization')) {
                        $this->id = 'id';
                        $this->source = 'attributes';
                        $this->entity = 'Organization';
                        $id = $request->request->get('organization');
                    } else {
                        $id = 0;
                    }
                }
                break;
            default:
                $id = 0;
        }

        switch ($this->entity) {
            case 'AttributeSpec':
                $sids = $this->getServiceIdsFromAsid($id, $sids);
                break;
            case 'AttributeValuePrincipal':
                if ($this->source === 'principal') {
                    $serviceIds = $this->em->getRepository('HexaaStorageBundle:Service')->findAllIdsByRelatedPrincipal($id);
                    foreach ($serviceIds as $serviceId) {
                        $sids[] = $serviceId;
                    }
                } else {
                    $avp = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
                    if ($avp) {
                        if ($avp->getServices()->count() == 0) {
                            $sids = $this->getServiceIdsFromAsid($avp->getAttributeSpecId(), $sids);
                        } else {
                            foreach ($avp->getServices() as $service) {
                                $sids[] = $service->getId();
                            }
                        }
                    }
                }
                break;
            case 'AttributeValueOrganization':
                if ($this->source === 'principal') {
                    $serviceIds = $this->em->getRepository('HexaaStorageBundle:Service')->findAllIdsByRelatedPrincipal($id);
                    foreach ($serviceIds as $serviceId) {
                        $sids[] = $serviceId;
                    }
                } else {
                    $avo = $this->em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
                    if ($avo) {
                        if ($avo->getServices()->count() == 0) {
                            $sids = $this->getServiceIdsFromAsid($avo->getAttributeSpecId(), $sids);
                        } else {
                            foreach ($avo->getServices() as $service) {
                                $sids[] = $service->getId();
                            }
                        }
                    }
                }
                break;
            case 'Consent':
                if ($this->source === 'principal') {
                    $serviceIds = $this->em->getRepository('HexaaStorageBundle:Service')->findAllIdsByRelatedPrincipal($id);
                    foreach ($serviceIds as $serviceId) {
                        $sids[] = $serviceId;
                    }
                } else {
                    $c = $this->em->getRepository('HexaaStorageBundle:Consent')->find($id);
                    if ($c) {
                        $sids[] = $c->getServiceId();
                    }
                }
                break;
            case 'Entitlement':
                $e = $this->em->getRepository('HexaaStorageBundle:Entitlement')->find($id);
                if ($e) {
                    $sids[] = $e->getService()->getId();
                }
                break;
            case 'EntitlementPack':
                $ep = $this->em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
                if ($ep) {
                    $sids[] = $ep->getService()->getId();
                }
                break;
            case 'Invitation':
                $i = $this->em->getRepository('HexaaStorageBundle:Invitation')->findOneBy(array('token' => $id));
                if ($i) {
                    if ($i->getOrganization()) {
                        $serviceIds = $this->em->getRepository('HexaaStorageBundle:Service')
                          ->findAllIdsByRelatedOrganization($i->getOrganization());
                        foreach ($serviceIds as $serviceId) {
                            $sids[] = $serviceId;
                        }
                    }
                }
                break;
            case 'Organization':
                $o = $this->em->getRepository('HexaaStorageBundle:Organization')->find($id);
                if ($o) {
                    $serviceIds = $this->em->getRepository('HexaaStorageBundle:Service')
                      ->findAllIdsByRelatedOrganization($o);
                    foreach ($serviceIds as $serviceId) {
                        $sids[] = $serviceId;
                    }
                }
                break;
            case 'Principal':
                if ($this->source === 'principal') {
                    $p = $id;
                } else {
                    if ($this->id === 'fedid') {
                        $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneBy(array('fedid' => $id));
                    } else {
                        $p = $this->em->getRepository('HexaaStorageBundle:Principal')->find($id);
                    }
                }
                if ($p) {
                    $serviceIds = $this->em->getRepository('HexaaStorageBundle:Service')->findAllIdsByRelatedPrincipal($p);
                    foreach ($serviceIds as $serviceId) {
                        $sids[] = $serviceId;
                    }
                }
                break;
            case 'Role':
                $r = $this->em->getRepository('HexaaStorageBundle:Role')->find($id);
                if ($r) {
                    $serviceIds = $this->em->getRepository('HexaaStorageBundle:Service')
                      ->findAllIdsByRelatedOrganization($r->getOrganization());
                    foreach ($serviceIds as $serviceId) {
                        $sids[] = $serviceId;
                    }
                }
                break;
            case 'Service':
                if ($this->id = 'token') {
                    $s = $this->em->getRepository('HexaaStorageBundle:Service')->findOneBy(array('enableToken' => $id));
                } else {
                    $s = $this->em->getRepository('HexaaStorageBundle:Service')->find($id);
                }
                if ($s) {
                    $sids[] = $s->getId();
                }
                break;
            case 'Link':
                if ($this->source = 'link') {
                    // this indicates link w/ neither service nor organization, do nothing as the call will fail.
                } else {
                    $link = $this->em->getRepository('HexaaStorageBundle:Link')->find($id);
                    if ($link) {
                        if ($link->getService()) {
                            $sids[] = $link->getServiceId();
                        } else {
                            if ($link->getOrganization()) {
                                $serviceIds = $this->em->getRepository('HexaaStorageBundle:Service')
                                  ->findAllIdsByRelatedOrganization($link->getOrganization());
                                foreach ($serviceIds as $serviceId) {
                                    $sids[] = $serviceId;
                                }
                            }
                        }
                    }
                }
        }

        return $sids;
    }

    protected function getServiceIdsFromAsid($asid, $sids)
    {
        $serviceIds = $this->em->createQueryBuilder()
          ->select('service.id')
          ->from('HexaaStorageBundle:ServiceAttributeSpec', 'sas')
          ->innerJoin('sas.service', 'service')
          ->innerJoin('sas.attributeSpec', 'attribute_spec')
          ->where('attribute_spec.id = :asid')
          ->andWhere('service.isEnabled = true')
          ->setParameter('asid', $asid)
          ->getQuery()
          ->getScalarResult();

        foreach ($serviceIds as $serviceId) {
            $sids[] = $serviceId['id'];
        }

        return $sids;
    }

    public function setHint($hint)
    {
        $this->entity = $hint['entity'];
        $this->id = $hint['id'];
        $this->source = $hint['source'];
    }
}