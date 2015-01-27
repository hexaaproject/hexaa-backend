<?php

namespace Hexaa\ApiBundle\EventListener;

use Hexaa\ApiBundle\Controller\PersonalAuthenticatedController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\Organization;
use Hexaa\StorageBundle\Entity\Service;
use Hexaa\ApiBundle\Controller\HexaaController;

class CheckPolicyListener {

    /* @var $em \Doctrine\ORM\EntityManager */
    private $em;
    /* @var $eh \Hexaa\ApiBundle\Handler\EntityHandler */
    private $eh;
    /* @var $accesslog \Monolog\Logger */
    private $accesslog;
    /* @var $accesslog \Monolog\Logger */
    private $errorlog;
    /* @var $accesslog \Monolog\Logger */
    private $loginlog;
    /* @var $accesslog \Monolog\Logger */
    private $modlog;
    private $admins;
    private $securityContext;
    /* @var $hookHandler \Hexaa\ApiBundle\Hook\HookHandler */
    private $hookHandler;

    public function __construct($em, $loginlog, $errorlog, $accesslog, $modlog,  $admins, $securityContext, $hookHandler, $entityHandler) {
        $this->em = $em;
        $this->accesslog = $accesslog;
        $this->loginlog = $loginlog;
        $this->errorlog = $errorlog;
        $this->modlog = $modlog;
        $this->admins = $admins;
        $this->securityContext = $securityContext;
        $this->hookHandler = $hookHandler;
        $this->eh = $entityHandler;
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
        
        if ($controller[0] instanceof HexaaController) {
            $controller[0]->setStuff($this->em, $this->eh, $this->accesslog, $this->errorlog, $this->modlog);
        }

        if ($controller[0] instanceof PersonalAuthenticatedController) {
            // Get current user
            $usr = $this->securityContext->getToken()->getUser();
            $p = $usr->getPrincipal();

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
        $globalControllerString = $controllerBase . "GlobalController::";
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
            case $newsControllerString . "cgetPrincipalsNewsAction":
                return $this->isAdmin($p);
                break;

            // Service manager (through service)
            case $newsControllerString . "cgetServicesNewsAction":
            case $entitlementControllerString . "postServiceEntitlementAction":
            case $entitlementPackControllerString . "postServiceEntitlementpackAction":
            case $serviceControllerString . "patchAction":
            case $serviceControllerString . "putAction":
            case $serviceControllerString . "deleteAction":
            case $serviceControllerString . "postLogoAction":
            case $serviceControllerString . "putNotifyspAction":
            case $serviceChildControllerString . "putAttributespecsAction":
            case $serviceChildControllerString . "putAttributespecAction":
            case $serviceChildControllerString . "deleteAttributespecAction":
            case $serviceChildControllerString . "putManagerAction":
            case $serviceChildControllerString . "putManagersAction":
            case $serviceChildControllerString . "cgetInvitationsAction":
                return ($this->isManagerOfService($request->attributes->get('id'), $p, $_controller) || $this->isAdmin($p));
                break;

            // Service manager (through entitlement)
            case $entitlementControllerString . "getEntitlementAction":
            case $entitlementControllerString . "patchEntitlementAction":
            case $entitlementControllerString . "putEntitlementAction":
            case $entitlementControllerString . "deleteEntitlementAction":
                $s = $this->eh->get('Entitlement', $request->attributes->get('id'), $_controller)->getService();
                return ($this->isManagerOfService($s, $p, $_controller) || $this->isAdmin($p));
                break;

            // Service manager (through entitlementPack)
            case $entitlementPackControllerString . "patchEntitlementpackAction":
            case $entitlementPackControllerString . "putEntitlementpackAction":
            case $entitlementPackControllerString . "deleteEntitlementpackAction":
            case $entitlementPackControllerString . "getEntitlementpackTokenAction":
            case $entitlementPackEntitlementControllerString . "deleteEntitlementAction":
            case $entitlementPackEntitlementControllerString . "putEntitlementsAction":
            case $entitlementPackEntitlementControllerString . "putEntitlementAction":
                $s = $this->eh->get('EntitlementPack', $request->attributes->get('id'), $_controller)->getService();
                return ($this->isManagerOfService($s, $p, $_controller) || $this->isAdmin($p));
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
            case $roleControllerString . "postOrganizationRoleAction":
            case $organizationChildControllerString . "cgetInvitationsAction":
                return ($this->isManagerOfOrganization($request->attributes->get('id'), $p, $_controller) || $this->isAdmin($p));
                break;

            // Organization manager (from request)
            case $attributeValueControllerString . "postAttributevalueorganizationAction":
                if ($request->request->has('organization')) {
                    return ($this->isManagerOfOrganization($request->request->get('organization'), $p, $_controller) || $this->isAdmin($p));
                } else
                    return true; // Let validation handle it, it will fail anyway.
                break;

            // Organization manager (from role)
            case $roleControllerString . "patchRoleAction":
            case $roleControllerString . "putRoleAction":
            case $roleControllerString . "deleteRoleAction":
            case $roleControllerString . "getRolePrincipalsAction":
            case $roleControllerString . "putRolePrincipalAction":
            case $roleControllerString . "putRolePrincipalsAction":
            case $roleControllerString . "deleteRolePrincipalAction":
            case $roleControllerString . "putRoleEntitlementAction":
            case $roleControllerString . "putRoleEntitlementsAction":
            case $roleControllerString . "deleteRoleEntitlementsAction":
                $r = $this->eh->get('Role',$request->attributes->get('id'), $_controller);
                $o = $r->getOrganization();
                return ($this->isManagerOfOrganization($o, $p, $_controller) || $this->isAdmin($p));
                break;

            // Organization manager (from attributeValueOrganization)
            case $attributeValueControllerString . "patchAttributevalueorganizationAction":
            case $attributeValueControllerString . "putAttributevalueorganizationAction":
            case $attributeValueControllerString . "deleteAttributevalueorganizationAction":
            case $attributeValueControllerString . "putAttributevalueorganizationServiceAction":
            case $attributeValueControllerString . "deleteAttributevalueorganizationServiceAction":
                $avo = $this->eh->get('AttributeValueOrganization',$request->attributes->get('id'), $_controller);
                return ($this->isManagerOfOrganization($avo->getOrganization(), $p, $_controller) || $this->isAdmin($p));
                break;

            // Organization member (from id)
            case $newsControllerString . "cgetOrganizationsNewsAction":
                return ($this->isMemberOfOrganization($request->attributes->get('id'), $p, $_controller) || $this->isAdmin($p));
                break;

            // Organization member (from role)
            case $roleControllerString . "getRoleAction":
            case $roleControllerString . "cgetRoleEntitlementsAction":
            case $roleControllerString . "cgetRolePrincipalsAction":
                $r = $this->eh->get('Role', $request->attributes->get('id'), $_controller);
                $o = $r->getOrganization();
                return ($this->isMemberOfOrganization($o, $p, $_controller) || $this->isAdmin($p));
                break;

            // Organization member (from attributeValueOrganization)
            case $attributeValueControllerString . "getAttributevalueorganizationAction":
            case $attributeValueControllerString . "cgetAttributevalueorganizationsServicesAction":
            case $attributeValueControllerString . "getAttributevalueorganizationServiceAction":
                $avo = $this->eh->get('AttributeValueOrganization', $request->attributes->get('id'), $_controller);
                $o = $avo->getOrganization();
                return ($this->isMemberOfOrganization($o, $p, $_controller) || $this->isAdmin($p));
                break;

            // Self or admin (AttributeValuePrincipal)
            case $attributeValueControllerString . "getAttributevalueprincipalAction":
            case $attributeValueControllerString . "putAttributevalueprincipalAction":
            case $attributeValueControllerString . "patchAttributevalueprincipalAction":
            case $attributeValueControllerString . "deleteAttributevalueprincipalAction":
            case $attributeValueControllerString . "cgetAttributevalueprincipalsServicesAction":
            case $attributeValueControllerString . "getAttributevalueprincipalsServiceAction":
            case $attributeValueControllerString . "putAttributevalueprincipalsServiceAction":
            case $attributeValueControllerString . "deleteAttributevalueprincipalServiceAction":
                $avp = $this->eh->get('AttributeValuePrincipal', $request->attributes->get('id'), $_controller);
                return (($avp->getPrincipal() === $p) || $this->isAdmin($p));
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
                    return ($this->isManagerOfService($request->attributes->get('id'), $p, $_controller) || $this->isAdmin($p));
                }
                break;
                

            // Self (from consent)
            case $consentControllerString . "getAction":
            case $consentControllerString . "putAction":
            case $consentControllerString . "patchAction":
                $c = $this->eh->get('Consent', $request->attributes->get('id'), $_controller);
                return ($c->getPrincipal() === $p);
                break;

            //Self (from request)
            case $consentControllerString . "postAction":
                if ($request->request->has('principal')) {
                    return ($request->request->get('principal') === $p->getId());
                } else
                    return true; // Will default to self
                break;


            // service & organization manager (from invitation request)
            case $invitationControllerString . "postInvitationAction":
                if ($request->request->has('service')) {
                    return ($this->isManagerOfService($request->request->get('service'), $p, $_controller) || $this->isAdmin($p));
                } else {
                    if ($request->request->has('organization')) {
                        return ($this->isManagerOfOrganization($request->request->get('organization'), $p, $_controller) || $this->isAdmin($p));
                    } else
                        return true; // Let validation handle it, it will fail anyway.
                }
                break;

            // service & organization manager (from invitation)
            case $invitationControllerString . "getInvitationAction":
            case $invitationControllerString . "getInvitationResendAction":
            case $invitationControllerString . "putInvitationAction":
            case $invitationControllerString . "patchInvitationAction":
            case $invitationControllerString . "deleteInvitationAction":
                $i = $this->eh->get('Invitation', $request->attributes->get('id'), $_controller);
                $s = $i->getService();
                $o = $i->getOrganization();
                if ($s instanceof Service) {
                    return ($this->isManagerOfService($s, $p, $_controller) || $this->isAdmin($p));
                }
                if ($o instanceof Organization) {
                    return ($this->isManagerOfOrganization($o, $p, $_controller) || $this->isAdmin($p));
                }
                return false; // This shouldn't happen, but lock them out, just to be sure.
                break;

            // service & organization manager (from organization and entitlementPack)
            case $organizationChildControllerString . "deleteEntitlementpacksAction":
                $o = $this->eh->get('Organization', $request->attributes->get('id'), $_controller);
                $ep = $this->eh->get('EntitlementPack', $request->attributes->get('epid'), $_controller);
                $s = $ep->getService();
                return ($this->isAdmin($p) || $this->isManagerOfOrganization($o, $p, $_controller) || $this->isManagerOfService($s, $p, $_controller));
                break;

            // service manager (from entitlementPack [epid])
            case $organizationChildControllerString . "putEntitlementpacksAcceptAction":
                $ep = $this->eh->get('EntitlementPack', $request->attributes->get('epid'), $_controller);
                $s = $ep->getService();
                return ($this->isManagerOfService($s, $p, $_controller) || $this->isAdmin($p));
                break;

            // Organization member & related service manager (from organization)
            case $organizationControllerString . "getAction":
                $o = $this->eh->get('Organization', $request->attributes->get('id'), $_controller);
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
                return ($this->isAdmin($p) || $this->isMemberOfOrganization($o, $p, $_controller) || in_array($p, $sManagers, true));
                break;
            
            // Service manager or related organization member
            case $serviceControllerString . "getAction":
                $s = $this->eh->get('Service', $request->attributes->get('id'), $_controller);
                $ss = $this->em->getRepository('HexaaStorageBundle:Service')->findAllByRelatedPrincipal($p);
                return ($this->isAdmin($p) || $this->isManagerOfService($s, $p, $_controller) || in_array($s, $ss, true));
                break;
            

            // No special permission required
            case $attributeSpecControllerString . "cgetAction":
            case $attributeSpecControllerString . "getAction":
            case $attributeSpecControllerString . "getServiceAction":
            case $consentControllerString . "cgetAction":
            case $consentControllerString . "getServiceAction":
            case $entitlementPackControllerString . "getEntitlementpackAction":
            case $entitlementPackControllerString . "cgetEntitlementpacksPublicAction":
            case $entitlementPackEntitlementControllerString . "cgetEntitlementsAction":
            case $globalControllerString . "cgetEntityidsAction":
            case $globalControllerString . "getPropertiesAction":
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
            case $serviceControllerString . "putEnableAction":
                return true;
                break;

            // Others
            default:
                return false;
        }
    }

    private function accessDeniedError($p, $_controller) {
        $this->errorlog->error("User " . $p->getFedid() . " has insufficient permissions in " . $_controller);
        throw new HttpException(403, "User " . $p->getFedid() . " has insufficient permissions in " . $_controller);
        return;
    }

    private function isManagerOfService($id, Principal $p, $_controller) {
        if ($id instanceof Service) {
            $s = $id;
        } else {
            $s = $this->eh->get('Service', $id, $_controller);
        }
        return $s->hasManager($p);
    }

    private function isManagerOfOrganization($id, Principal $p, $_controller) {
        if ($id instanceof Organization) {
            $o = $id;
        } else {
            $o = $this->eh->get('Organization', $id, $_controller);
        }
        return $o->hasManager($p);
    }

    private function isMemberOfOrganization($id, Principal $p, $_controller) {
        if ($id instanceof Organization) {
            $o = $id;
        } else {
            $o = $this->eh->get('Organization', $id, $_controller);
        }
        return $o->hasPrincipal($p);
    }

    private function isAdmin(Principal $p) {
        return in_array($p->getFedid(), $this->admins);
    }

}
