<?php

namespace Hexaa\ApiBundle\EventListener;

use Hexaa\ApiBundle\Controller\PersonalAuthenticatedController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\Organization;
use Hexaa\StorageBundle\Entity\Service;

class CheckPolicyListener {

    private $em;
    private $loginlog;
    private $errorlog;
    private $admins;
    private $securityContext;
    private $hookHandler;

    public function __construct($em, $loginlog, $errorlog, $admins, $securityContext, $hookHandler) {
        $this->em = $em;
        $this->loginlog = $loginlog;
        $this->errorlog = $errorlog;
        $this->admins = $admins;
        $this->securityContext = $securityContext;
        $this->hookHandler = $hookHandler;
    }

    public function onKernelController(FilterControllerEvent $event) {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof PersonalAuthenticatedController) {
            // Get current user
            $usr = $this->securityContext->getToken()->getUser();
            $p = $this->em->getRepository('HexaaStorageBundle:Principal')->findOneByFedid($usr->getUsername());

            // Get controller string
            $_controller = $event->getRequest()->attributes->get('_controller');

            // Get masterkey type
            $masterkey = $p->getToken()->getMasterkey();
            
            // Check persmissions
            if (!($this->checkPermission($p, $_controller, $event->getRequest()) && $this->hookHandler->handleMasterKeyHook($masterkey, $p, $_controller))) {
                $this->accessDeniedError($p, $_controller);
            }
        }
    }

    private function checkPermission(Principal $p, $_controller, $request) {
        // Base string
        $controllerBase = "Hexaa\\ApiBundle\\Controller\\";
        //Controller strings
        $attributeSpecControllerString = $controllerBase . "AttributespecController::";
        $attributeValueControllerString = $controllerBase . "AttributevalueController::";
        $consentControllerString = $controllerBase . "ConsentController::";
        $entitlementControllerString = $controllerBase . "EntitlementController::";
        $entitlementPackEntitlementControllerString = $controllerBase . "EntitlementpackEntitlementController::";
        $entitlementPackControllerString = $controllerBase . "EntitlementpackController::";
        $entityidControllerString = $controllerBase . "EntityidController::";
        $hexaaControllerString = $controllerBase . "HexaaController::";
        $invitationControllerString = $controllerBase . "InvitationController::";
        $newsControllerString = $controllerBase . "NewsController::";
        $organizationChildControllerString = $controllerBase . "OrganizationChildController::";
        $organizationControllerString = $controllerBase . "OrganizationController::";
        $principalControllerString = $controllerBase . "PrincipalController::";
        $roleControllerString = $controllerBase . "RoleController::";
        $serviceChildControllerString = $controllerBase . "ServiceChildController::";
        $serviceControllerString = $controllerBase . "ServiceController::";

        // Check permission depending on controller::action
        switch ($_controller) {
            // Admin only
            case $attributeSpecControllerString . "postAction":
            case $attributeSpecControllerString . "putAction":
            case $attributeSpecControllerString . "patchAction":
            case $attributeSpecControllerString . "deleteAction":
            case $organizationChildControllerString . "putMemberAction":
            case $principalControllerString . "cgetPrincipalsAction":
            case $principalControllerString . "postPrincipalAction":
            case $principalControllerString . "putPrincipalAction":
            case $principalControllerString . "patchPrincipalAction":
            case $principalControllerString . "deletePrincipalFedidAction":
            case $principalControllerString . "deletePrincipalIdAction":
            case $entityidControllerString . "getEntityidrequestAcceptAction":
            case $entityidControllerString . "getEntityidrequestRejectAction":
            case $newsControllerString . "cgetPrincipalsNewsAction":
                return $this->isAdmin($p);
                break;

            // Service manager (through service)
            case $newsControllerString . "cgetServicesNewsAction":
            case $serviceChildControllerString . "postEntitlementAction":
            case $serviceChildControllerString . "postEntitlementpackAction":
            case $serviceControllerString . "patchAction":
            case $serviceControllerString . "putAction":
            case $serviceControllerString . "deleteAction":
            case $serviceControllerString . "postLogoAction":
            case $serviceChildControllerString . "putAttributespecsAction":
            case $serviceChildControllerString . "putAttributespecAction":
            case $serviceChildControllerString . "deleteAttributespecAction":
            case $serviceChildControllerString . "putManagerAction":
            case $serviceChildControllerString . "putManagersAction":
            case $serviceChildControllerString . "cgetInvitationsAction":
                return ($this->isManagerOfService($request->attributes->get('id'), $p) || $this->isAdmin($p));
                break;

            // Service manager (through entitlement)
            case $entitlementControllerString . "getAction":
            case $entitlementControllerString . "patchAction":
            case $entitlementControllerString . "putAction":
            case $entitlementControllerString . "deleteAction":
                $s = $this->getEntitlement($request->attributes->get('id'))->getService();
                return ($this->isManagerOfService($s, $p) || $this->isAdmin($p));
                break;

            // Service manager (through entitlementPack)
            case $entitlementPackControllerString . "patchAction":
            case $entitlementPackControllerString . "putAction":
            case $entitlementPackControllerString . "deleteAction":
            case $entitlementPackControllerString . "getTokenAction":
            case $entitlementPackEntitlementControllerString . "deleteEntitlementAction":
            case $entitlementPackEntitlementControllerString . "putEntitlementsAction":
            case $entitlementPackEntitlementControllerString . "putEntitlementAction":
                $s = $this->getEntitlementPack($request->attributes->get('id'))->getService();
                return ($this->isManagerOfService($s, $p) || $this->isAdmin($p));
                break;

            // Organization manager (from id)
            case $organizationControllerString . "patchAction":
            case $organizationControllerString . "putAction":
            case $organizationControllerString . "deleteAction":
            case $organizationChildControllerString . "putEntitlementpackAction":
            case $organizationChildControllerString . "deleteManagerAction":
            case $organizationChildControllerString . "putManagersAction":
            case $organizationChildControllerString . "putManagerAction":
            case $organizationChildControllerString . "deleteMemberAction":
            case $organizationChildControllerString . "putMembersAction":
            case $organizationChildControllerString . "putEntitlementpacksAction":
            case $organizationChildControllerString . "putEntitlementpacksTokenAction":
            case $organizationChildControllerString . "cgetAttributespecsAction":
            case $organizationChildControllerString . "cgetAttributespecsAttributevalueorganizationsAction":
            case $organizationChildControllerString . "cgetAttributevalueorganizationAction":
            case $organizationChildControllerString . "postRoleAction":
            case $organizationChildControllerString . "cgetInvitationsAction":
                return ($this->isManagerOfOrganization($request->attributes->get('id'), $p) || $this->isAdmin($p));
                break;

            // Organization manager (from request)
            case $attributeValueControllerString . "postAttributevalueorganizationAction":
                if ($request->request->has('organization')) {
                    return ($this->isManagerOfOrganization($request->request->get('organization'), $p) || $this->isAdmin($p));
                } else
                    return true; // Let validation handle it, it will fail anyway.
                break;

            // Organization manager (from role)
            case $roleControllerString . "patchAction":
            case $roleControllerString . "putAction":
            case $roleControllerString . "deleteAction":
            case $roleControllerString . "getPrincipalsAction":
            case $roleControllerString . "putPrincipalAction":
            case $roleControllerString . "putPrincipalsAction":
            case $roleControllerString . "deletePrincipalAction":
            case $roleControllerString . "putEntitlementAction":
            case $roleControllerString . "putEntitlementsAction":
            case $roleControllerString . "deleteEntitlementsAction":
                $r = $this->getRole($request->attributes->get('id'));
                $o = $r->getOrganization();
                return ($this->isManagerOfOrganization($o, $p) || $this->isAdmin($p));
                break;

            // Organization manager (from attributeValueOrganization)
            case $attributeValueControllerString . "patchAttributevalueorganizationAction":
            case $attributeValueControllerString . "putAttributevalueorganizationAction":
            case $attributeValueControllerString . "deleteAttributevalueorganizationAction":
            case $attributeValueControllerString . "putAttributevalueorganizationServiceAction":
            case $attributeValueControllerString . "deleteAttributevalueorganizationServiceAction":
                $avo = $this->getAttributeValueOrganization($request->attributes->get('id'));
                return ($this->isManagerOfOrganization($avo->getOrganization(), $p) || $this->isAdmin($p));
                break;

            // Organization member (from id)
            case $newsControllerString . "cgetOrganizationsNewsAction":
                return ($this->isMemberOfOrganization($request->attributes->get('id'), $p) || $this->isAdmin($p));
                break;

            // Organization member (from role)
            case $roleControllerString . "getAction":
            case $roleControllerString . "cgetEntitlementsAction":
            case $roleControllerString . "cgetPrincipalsAction":
                $r = $this->getRole($request->attributes->get('id'));
                $o = $r->getOrganization();
                return ($this->isManagerOfOrganization($o, $p) || $this->isAdmin($p));
                break;

            // Organization member (from attributeValueOrganization)
            case $attributeValueControllerString . "getAttributevalueorganizationAction":
            case $attributeValueControllerString . "cgetAttributevalueorganizationsServicesAction":
            case $attributeValueControllerString . "getAttributevalueorganizationServiceAction":
                $avo = $this->getAttributeValueOrganization($request->attributes->get('id'));
                $o = $avo->getOrganization();
                return ($this->isMemberOfOrganization($o, $p) || $this->isAdmin($p));
                break;

            // Special cases
            // 
            // 
            // 
            // 
            // 
            // Self or admin (AttributeValuePrincipal)
            case $attributeValueControllerString . "getAttributevalueprincipalAction":
            case $attributeValueControllerString . "putAttributevalueprincipalAction":
            case $attributeValueControllerString . "patchAttributevalueprincipalAction":
            case $attributeValueControllerString . "deleteAttributevalueprincipalAction":
            case $attributeValueControllerString . "cgetAttributevalueprincipalsServicesAction":
            case $attributeValueControllerString . "getAttributevalueprincipalsServiceAction":
            case $attributeValueControllerString . "putAttributevalueprincipalsServiceAction":
            case $attributeValueControllerString . "deleteAttributevalueprincipalServiceAction":
                $avp = $this->getAttributeValuePrincipal($request->attributes->get('id'));
                return (($avp->getPrincipal() === $p) || $this->isAdmin($p));
                break;

            // Self or admin (EntityidRequest)
            case $entityidControllerString . "getEntityidrequestAction":
            case $entityidControllerString . "putEntityidrequestAction":
            case $entityidControllerString . "patchEntityidrequestAction":
            case $entityidControllerString . "deleteEntityidrequestAction":
                $er = $this->getEntityidRequest($request->attributes->get('id'));
                return (($er->getRequester() === $p) || $this->isAdmin($p));
                break;

            // Self or admin (from request)
            case $attributeValueControllerString . "postAttributevalueprincipalAction":
                if ($request->request->has('principal')) {
                    return (($request->request->get('principal') === $p->getId()) || $this->isAdmin($p));
                } else
                    return true; // Will default to self
                break;
                
            // Self or service manager (from service id)
            case $serviceChildControllerString . "deleteManagerAction":
                if ($request->attributes->get('pid') === $p->getId()){
                    return true;
                } else {
                    return ($this->isManagerOfService($request->attributes->get('id'), $p) || $this->isAdmin($p));
                }
                break;
                

            // Self (from consent)
            case $consentControllerString . "getAction":
            case $consentControllerString . "putAction":
            case $consentControllerString . "patchAction":
                $c = $this->getConsent($request->attributes->get('id'));
                return ($c->getPrincipal() === $p);
                break;

            //Self (from request)
            case $consentControllerString . "postAction":
                if ($request->request->has('principal')) {
                    return ($request->request->get('principal') === $p->getId());
                } else
                    return true; // Will default to self
                break;


            // Invitation POST (service & organization manager from invitation request)
            case $invitationControllerString . "postAction":
                if ($request->request->has('service')) {
                    return ($this->isManagerOfService($request->request->get('service'), $p) || $this->isAdmin($p));
                } else {
                    if ($request->request->has('organization')) {
                        return ($this->isManagerOfOrganization($request->request->get('organization'), $p) || $this->isAdmin($p));
                    } else
                        return true; // Let validation handle it, it will fail anyway.
                }
                break;

            // Other Invitation endpoints (service & organization manager from invitation)
            case $invitationControllerString . "getInvitationAction":
            case $invitationControllerString . "getInvitationResendAction":
            case $invitationControllerString . "putInvitationAction":
            case $invitationControllerString . "patchInvitationAction":
            case $invitationControllerString . "deleteInvitationAction":
                $i = $this->getInvitation($request->attributes->get('id'));
                $s = $i->getService();
                $o = $i->getInvitation();
                if ($s instanceof Service) {
                    return ($this->isManagerOfService($s, $p) || $this->isAdmin($p));
                }
                if ($o instanceof Organization) {
                    return ($this->isManagerOfOrganization($o, $p) || $this->isAdmin($p));
                }
                return false; // This shouldn't happen, but lock them out, just to be sure.
                break;

            // EntitlementPack unlink (service & organization manager from organization and entitlementPack)
            case $organizationChildControllerString . "deleteEntitlementpacksAction":
                $o = $this->getOrganization($request->attributes->get('id'));
                $ep = $this->getEntitlementPack($request->attributes->get('epid'));
                $s = $ep->getService();
                return ($this->isAdmin($p) || $this->isManagerOfOrganization($o, $p) || $this->isManagerOfService($s, $p));
                break;

            // EntitlementPack accept (service manager from entitlementPack)
            case $organizationChildControllerString . "putEntitlementpacksAcceptAction":
                $ep = $this->getEntitlementPack($request->attributes->get('epid'));
                $s = $ep->getService();
                return ($this->isManagerOfService($s, $p) || $this->isAdmin($p));
                break;

            // Organization member & related service manager (from organization)
            case $organizationControllerString . "getAction":
                $o = $this->getOrganization($request->attributes->get('id'));
                $sManagers = $this->em->createQueryBuilder()
                        ->select('p')
                        ->from('HexaaStorageBundle:Principal', 'p')
                        ->from('HexaaStorageBundle:OrganizationEntitlementPack', 'oep')
                        ->leftJoin('oep.entitlementPack', 'ep')
                        ->leftJoin('ep.service', 's')
                        ->where('oep.organization = :o')
                        ->andWhere('p MEMBER OF s.managers')
                        ->setParameters(array(':o' => $o))
                        ->getQuery()
                        ->getResult();
                return ($this->isAdmin($p) || $this->isMemberOfOrganization($o, $p) || in_array($p, $sManagers, true));
                break;
            
            // Service manager or related organization member
            case $serviceControllerString . "getAction":
                $s = $this->getService($request->attributes->get('id'));
                $ss = $this->em->getRepository('HexaaStorageBundle:Service')->findAllByRelatedPrincipal($p);
                return ($this->isAdmin($p) || $this->isManagerOfService($s, $p) || in_array($s, $ss, true));
                break;
            

            // No special permission required
            case $attributeSpecControllerString . "cgetAction":
            case $attributeSpecControllerString . "getAction":
            case $attributeSpecControllerString . "getServiceAction":
            case $consentControllerString . "cgetAction":
            case $consentControllerString . "getServiceAction":
            case $entitlementPackControllerString . "getAction":
            case $entitlementPackControllerString . "cgetPublicAction":
            case $entitlementPackEntitlementControllerString . "cgetEntitlementsAction":
            case $entityidControllerString . "cgetEntityidsAction":
            case $entityidControllerString . "cgetEntityidrequestsAction":
            case $entityidControllerString . "postEntityidrequestAction":
            case $hexaaControllerString . "getPropertiesAction":
            case $invitationControllerString . "getInvitationAcceptEmailAction":
            case $invitationControllerString . "getInvitationAcceptTokenAction":
            case $invitationControllerString . "getInvitationRejectEmailAction":
            case $newsControllerString . "getPrincipalNewsAction":
            case $organizationChildControllerString . "cgetManagersAction":
            case $organizationChildControllerString . "getManagerCountAction":
            case $organizationChildControllerString . "getMemberCountAction":
            case $organizationChildControllerString . "cgetMembersAction":
            case $organizationChildControllerString . "cgetEntitlementsAction":
            case $organizationChildControllerString . "cgetEntitlementpacksAction":
            case $organizationChildControllerString . "cgetRolesAction":
            case $organizationControllerString . "cgetAction":
            case $organizationControllerString . "postAction":
            case $principalControllerString . "getPrincipalIsadminAction":
            case $principalControllerString . "getPrincipalSelfAction":
            case $principalControllerString . "getPrincipalIdAction":
            case $principalControllerString . "getPrincipalFedidAction":
            case $principalControllerString . "cgetPrincipalInvitationsAction":
            case $principalControllerString . "cgetPrincipalAttributespecsAction":
            case $principalControllerString . "cgetPrincipalAttributespecsAttributevalueprincipalsAction":
            case $principalControllerString . "cgetPrincipalAttributevalueprincipalAction":
            case $principalControllerString . "cgetManagerServicesAction":
            case $principalControllerString . "cgetManagerOrganizationsAction":
            case $principalControllerString . "cgetMemberOrganizationsAction":
            case $principalControllerString . "cgetPrincipalEntitlementsAction":
            case $principalControllerString . "cgetPrincipalServicesRelatedAction":
            case $principalControllerString . "cgetPrincipalRolesAction":
            case $principalControllerString . "deletePrincipalAction":
            case $serviceChildControllerString . "cgetManagersAction":
            case $serviceChildControllerString . "getManagerCountAction":
            case $serviceChildControllerString . "cgetAttributespecsAction":
            case $serviceChildControllerString . "cgetEntitlementpackRequestsAction":
            case $serviceChildControllerString . "cgetOrganizationsAction":
            case $serviceChildControllerString . "cgetEntitlementsAction":
            case $serviceChildControllerString . "cgetEntitlementpacksAction":
            case $serviceControllerString . "cgetAction":
            case $serviceControllerString . "postAction":
                return true;
                break;

            // Others
            default:
                return false;
        }
    }

    private function notFoundError($objectName, $id = null, $token = null) {
        if ($id !== null) {
            $this->errorlog->error("The requested " . $objectName . " with id=" . $id . " was not found");
            throw new HttpException(404, $objectName . " not found.");
        } else {
            if ($token !== null) {
                $this->errorlog->error("The requested " . $objectName . " with token=" . $token . " was not found");
                throw new HttpException(404, $objectName . " not found.");
            } else {
                $this->errorlog->error("The requested " . $objectName . " was not found");
                throw new HttpException(404, $objectName . " not found.");
            }
        }
    }

    private function accessDeniedError($p, $_controller) {
        $this->errorlog->error("User " . $p->getFedid() . " has insufficent permissions in " . $_controller);
        throw new HttpException(403, "Forbidden");
        return;
    }

    private function getEntitlement($id) {
        $e = $this->em->getRepository('HexaaStorageBundle:Entitlement')->find($id);
        if (!$e) {
            $this->notFoundError("Entitlement", $id);
        }

        return $e;
    }

    private function getAttributeValueOrganization($id) {
        $avo = $this->em->getRepository('HexaaStorageBundle:AttributeValueOrganization')->find($id);
        if (!$avo) {
            $this->notFoundError("AttributeValueOrganization", $id);
        }

        return $avo;
    }

    private function getAttributeValuePrincipal($id) {
        $avp = $this->em->getRepository('HexaaStorageBundle:AttributeValuePrincipal')->find($id);
        if (!$avp) {
            $this->notFoundError("AttributeValuePrincipal", $id);
        }

        return $avp;
    }

    private function getEntityidRequest($id) {
        $er = $this->em->getRepository('HexaaStorageBundle:EntityidRequest')->find($id);
        if (!$er) {
            $this->notFoundError("EntityidRequest", $id);
        }

        return $er;
    }

    private function getConsent($id) {
        $c = $this->em->getRepository('HexaaStorageBundle:Consent')->find($id);
        if (!$c) {
            $this->notFoundError("Consent", $id);
        }

        return $c;
    }

    private function getEntitlementPack($id) {
        $ep = $this->em->getRepository('HexaaStorageBundle:EntitlementPack')->find($id);
        if (!$ep) {
            $this->notFoundError("EntitlementPack", $id);
        }

        return $ep;
    }

    private function getEntitlementPackByToken($token) {
        $ep = $this->em->getRepository('HexaaStorageBundle:EntitlementPack')->findOneByToken($id);
        if (!$ep) {
            $this->notFoundError("EntitlementPack", null, $token);
        }

        return $ep;
    }

    private function getInvitation($id) {
        $i = $this->em->getRepository('HexaaStorageBundle:Invitation')->find($id);
        if (!$i) {
            $this->notFoundError("Invitation", $id);
        }

        return $i;
    }

    private function getRole($id) {
        $r = $this->em->getRepository('HexaaStorageBundle:Role')->find($id);
        if (!$r) {
            $this->notFoundError("Role", $id);
        }

        return $r;
    }

    private function getService($id) {
        $s = $this->em->getRepository('HexaaStorageBundle:Service')->find($id);
        if (!$s) {
            $this->notFoundError("Service", $id);
        }

        return $s;
    }

    private function getOrganization($id) {
        $o = $this->em->getRepository('HexaaStorageBundle:Organization')->find($id);
        if (!$o) {
            $this->notFoundError("Organization", $id);
        }

        return $o;
    }

    private function isManagerOfService($id, Principal $p) {
        if ($id instanceof Service) {
            $s = $id;
        } else {
            $s = $this->getService($id);
        }
        return $s->hasManager($p);
    }

    private function isManagerOfOrganization($id, Principal $p) {
        if ($id instanceof Organization) {
            $o = $id;
        } else {
            $o = $this->getOrganization($id);
        }
        return $o->hasManager($p);
    }

    private function isMemberOfOrganization($id, Principal $p) {
        if ($id instanceof Organization) {
            $o = $id;
        } else {
            $o = $this->getOrganization($id);
        }
        return $o->hasPrincipal($p);
    }

    private function isAdmin(Principal $p) {
        return in_array($p->getFedid(), $this->admins);
    }

}
