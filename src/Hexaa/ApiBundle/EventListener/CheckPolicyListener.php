<?php

namespace Hexaa\ApiBundle\EventListener;

use Hexaa\ApiBundle\Controller\HexaaController;
use Hexaa\ApiBundle\Controller\PersonalAuthenticatedController;
use Hexaa\ApiBundle\Hook\MasterKeyHook\MasterKeyHook;
use Hexaa\StorageBundle\Entity\Organization;
use Hexaa\StorageBundle\Entity\Principal;
use Hexaa\StorageBundle\Entity\Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\SecurityContext;

class CheckPolicyListener {

    //Controller strings
    const attributeSpecControllerString = "Hexaa\\ApiBundle\\Controller\\AttributespecController::";
    const attributeValueControllerString = "Hexaa\\ApiBundle\\Controller\\AttributevalueController::";
    const consentControllerString = "Hexaa\\ApiBundle\\Controller\\ConsentController::";
    const entitlementControllerString = "Hexaa\\ApiBundle\\Controller\\EntitlementController::";
    const entitlementPackEntitlementControllerString = "Hexaa\\ApiBundle\\Controller\\EntitlementpackEntitlementController::";
    const entitlementPackControllerString = "Hexaa\\ApiBundle\\Controller\\EntitlementpackController::";
    const globalControllerString = "Hexaa\\ApiBundle\\Controller\\GlobalController::";
    const invitationControllerString = "Hexaa\\ApiBundle\\Controller\\InvitationController::";
    const newsControllerString = "Hexaa\\ApiBundle\\Controller\\NewsController::";
    const organizationChildControllerString = "Hexaa\\ApiBundle\\Controller\\OrganizationChildController::";
    const organizationControllerString = "Hexaa\\ApiBundle\\Controller\\OrganizationController::";
    const principalControllerString = "Hexaa\\ApiBundle\\Controller\\PrincipalController::";
    const roleControllerString = "Hexaa\\ApiBundle\\Controller\\RoleController::";
    const securityDomainControllerString = "Hexaa\\ApiBundle\\Controller\\SecurityDomainController::";
    const serviceChildControllerString = "Hexaa\\ApiBundle\\Controller\\ServiceChildController::";
    const serviceControllerString = "Hexaa\\ApiBundle\\Controller\\ServiceController::";


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
    /* @var $securityContext SecurityContext */
    private $securityContext;
    /* @var $hookHandler \Hexaa\ApiBundle\Hook\HookHandler */
    private $hookHandler;

    private $idsToLog;

    public function __construct($em, $loginlog, $errorlog, $accesslog, $modlog, $admins, $securityContext, $hookHandler, $entityHandler) {
        $this->em = $em;
        $this->accesslog = $accesslog;
        $this->loginlog = $loginlog;
        $this->errorlog = $errorlog;
        $this->modlog = $modlog;
        $this->admins = $admins;
        $this->securityContext = $securityContext;
        $this->hookHandler = $hookHandler;
        $this->eh = $entityHandler;
        $this->idsToLog = array();
    }

    public function onKernelController(FilterControllerEvent $event) {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            throw new HttpException(500, "Server made a boo boo"); // Don't let it slip through anyways.
        }

        if ($controller[0] instanceof HexaaController) {

            if ($event->getRequest()->query->has('verbose')) {
                switch($event->getRequest()->get('verbose')) {
                    case "expanded":
                        $groups = array("expanded");
                        break;
                    case "minimal":
                        $groups = array("minimal");
                        break;
                    default:
                        $groups = array("normal");
                }
            } else {
                $groups = array("normal");
            }
            $event->getRequest()->attributes->set("groups", $groups);

            $controller[0]->setStuff($this->em, $this->eh, $this->accesslog, $this->errorlog, $this->modlog);
        }

        if ($controller[0] instanceof PersonalAuthenticatedController) {
            // Get current user
            /* @var $p Principal */
            $p = $this->securityContext->getToken()->getUser()->getPrincipal();

            // Get controller string
            $_controller = $event->getRequest()->attributes->get('_controller');

            // Get scoped key type
            $scopedKey = $p->getToken()->getMasterkey();

            // Get scoped key hook class
            $className = "Hexaa\\ApiBundle\\Hook\\MasterKeyHook\\" . $scopedKey;
            if (class_exists($className)) {
                $masterKeyHook = new $className($this->em, $p, $_controller);
                if (!$masterKeyHook instanceof MasterKeyHook) {
                    $this->errorlog->error('[checkPolicyListener] Scoped key named "' . $className . '" is not an instance of MasterKeyHook.');
                    throw new HttpException(500, "No MasterKeyHook defined for " . $scopedKey);
                }
            } else {
                $this->errorlog->error('[checkPolicyListener] Scoped key named "' . $className . '" could not be found.');
                throw new HttpException(500, "No MasterKeyHook defined for " . $scopedKey);
            }

            // Check persmissions
            if (
                (!$this->isAdmin($p, $event->getRequest()))
                && !(
                    $this->checkPermission($p, $_controller, $event->getRequest(), $scopedKey)
                    && $this->hookHandler->handleMasterKeyHook($masterKeyHook))
            ) {
                $this->accessDeniedError($p, $_controller);
            }

        }
    }

    private function checkPermission(Principal $p, $_controller, $request, $scopedKey) {
        // Check permission depending on controller::action
        switch($_controller) {

            // Admin only
            case CheckPolicyListener::attributeSpecControllerString . "postAction":
            case CheckPolicyListener::attributeSpecControllerString . "putAction":
            case CheckPolicyListener::attributeSpecControllerString . "patchAction":
            case CheckPolicyListener::attributeSpecControllerString . "deleteAction":
            case CheckPolicyListener::organizationChildControllerString . "putMemberAction":
            case CheckPolicyListener::principalControllerString . "cgetPrincipalsAction":
            case CheckPolicyListener::principalControllerString . "postPrincipalAction":
            case CheckPolicyListener::principalControllerString . "patchPrincipalAction":
            case CheckPolicyListener::principalControllerString . "deletePrincipalFedidAction":
            case CheckPolicyListener::principalControllerString . "deletePrincipalIdAction":
            case CheckPolicyListener::newsControllerString . "cgetPrincipalsNewsAction":
            case CheckPolicyListener::securityDomainControllerString . "cgetAction":
            case CheckPolicyListener::securityDomainControllerString . "getAction":
            case CheckPolicyListener::securityDomainControllerString . "postAction":
            case CheckPolicyListener::securityDomainControllerString . "putAction":
            case CheckPolicyListener::securityDomainControllerString . "patchAction":
            case CheckPolicyListener::securityDomainControllerString . "deleteAction":
            case CheckPolicyListener::globalControllerString . "cgetScopedkeysAction":
                return $this->isAdmin($p, $request);
                break;

            // Service manager (through service)
            case CheckPolicyListener::newsControllerString . "cgetServicesNewsAction":
            case CheckPolicyListener::entitlementControllerString . "postServiceEntitlementAction":
            case CheckPolicyListener::entitlementPackControllerString . "postServiceEntitlementpackAction":
            case CheckPolicyListener::serviceControllerString . "patchAction":
            case CheckPolicyListener::serviceControllerString . "putAction":
            case CheckPolicyListener::serviceControllerString . "deleteAction":
            case CheckPolicyListener::serviceControllerString . "postLogoAction":
            case CheckPolicyListener::serviceControllerString . "putNotifyspAction":
            case CheckPolicyListener::serviceChildControllerString . "putAttributespecsAction":
            case CheckPolicyListener::serviceChildControllerString . "putAttributespecAction":
            case CheckPolicyListener::serviceChildControllerString . "deleteAttributespecAction":
            case CheckPolicyListener::serviceChildControllerString . "putManagerAction":
            case CheckPolicyListener::serviceChildControllerString . "putManagersAction":
            case CheckPolicyListener::serviceChildControllerString . "cgetInvitationsAction":
                $this->idsToLog['id'] = $request->attributes->get('id');
                return $this->isManagerOfService($request->attributes->get('id'), $p, $_controller, $scopedKey);
                break;

            // Service manager (through entitlement)
            case CheckPolicyListener::entitlementControllerString . "getEntitlementAction":
            case CheckPolicyListener::entitlementControllerString . "patchEntitlementAction":
            case CheckPolicyListener::entitlementControllerString . "putEntitlementAction":
            case CheckPolicyListener::entitlementControllerString . "deleteEntitlementAction":
                $s = $this->eh->get('Entitlement', $request->attributes->get('id'), $_controller)->getService();
                $this->idsToLog['id'] = $request->attributes->get('id');

                return $this->isManagerOfService($s, $p, $_controller, $scopedKey);
                break;

            // Service manager (through entitlementPack)
            case CheckPolicyListener::entitlementPackControllerString . "patchEntitlementpackAction":
            case CheckPolicyListener::entitlementPackControllerString . "putEntitlementpackAction":
            case CheckPolicyListener::entitlementPackControllerString . "deleteEntitlementpackAction":
            case CheckPolicyListener::entitlementPackControllerString . "getEntitlementpackTokenAction":
            case CheckPolicyListener::entitlementPackControllerString . "cgetEntitlementpackTokensAction":
            case CheckPolicyListener::entitlementPackEntitlementControllerString . "deleteEntitlementAction":
            case CheckPolicyListener::entitlementPackEntitlementControllerString . "putEntitlementsAction":
            case CheckPolicyListener::entitlementPackEntitlementControllerString . "putEntitlementAction":
                $s = $this->eh->get('EntitlementPack', $request->attributes->get('id'), $_controller)->getService();
                $this->idsToLog['id'] = $request->attributes->get('id');

                return $this->isManagerOfService($s, $p, $_controller, $scopedKey);
                break;

            // Organization manager (from id)
            case CheckPolicyListener::organizationControllerString . "patchAction":
            case CheckPolicyListener::organizationControllerString . "putAction":
            case CheckPolicyListener::organizationControllerString . "deleteAction":
            case CheckPolicyListener::organizationChildControllerString . "putEntitlementpackAction":
            case CheckPolicyListener::organizationChildControllerString . "deleteManagerAction":
            case CheckPolicyListener::organizationChildControllerString . "putManagersAction":
            case CheckPolicyListener::organizationChildControllerString . "putManagerAction":
            case CheckPolicyListener::organizationChildControllerString . "deleteMemberAction":
            case CheckPolicyListener::organizationChildControllerString . "putMembersAction":
            case CheckPolicyListener::organizationChildControllerString . "putEntitlementpacksAction":
            case CheckPolicyListener::organizationChildControllerString . "putEntitlementpacksTokenAction":
            case CheckPolicyListener::organizationChildControllerString . "cgetAttributespecsAction":
            case CheckPolicyListener::organizationChildControllerString . "cgetAttributespecsAttributevalueorganizationsAction":
            case CheckPolicyListener::organizationChildControllerString . "cgetAttributevalueorganizationAction":
            case CheckPolicyListener::roleControllerString . "postOrganizationRoleAction":
            case CheckPolicyListener::organizationChildControllerString . "cgetInvitationsAction":
                $this->idsToLog['id'] = $request->attributes->get('id');
                return $this->isManagerOfOrganization($request->attributes->get('id'), $p, $_controller, $scopedKey);
                break;

            // Organization manager (from request)
            case CheckPolicyListener::attributeValueControllerString . "postAttributevalueorganizationAction":
                if ($request->request->has('organization')) {
                    $this->idsToLog['organization'] = $request->request->get('organization');
                    return $this->isManagerOfOrganization($request->request->get('organization'), $p, $_controller, $scopedKey);
                } else
                    return true; // Let validation handle it, it will fail anyway.
                break;

            // Organization manager (from role)
            case CheckPolicyListener::roleControllerString . "patchRoleAction":
            case CheckPolicyListener::roleControllerString . "putRoleAction":
            case CheckPolicyListener::roleControllerString . "deleteRoleAction":
            case CheckPolicyListener::roleControllerString . "getRolePrincipalsAction":
            case CheckPolicyListener::roleControllerString . "putRolePrincipalAction":
            case CheckPolicyListener::roleControllerString . "putRolePrincipalsAction":
            case CheckPolicyListener::roleControllerString . "deleteRolePrincipalAction":
            case CheckPolicyListener::roleControllerString . "putRoleEntitlementAction":
            case CheckPolicyListener::roleControllerString . "putRoleEntitlementsAction":
            case CheckPolicyListener::roleControllerString . "deleteRoleEntitlementsAction":
                $r = $this->eh->get('Role', $request->attributes->get('id'), $_controller);
                $this->idsToLog['id'] = $request->attributes->get('id');
                $o = $r->getOrganization();

                return $this->isManagerOfOrganization($o, $p, $_controller, $scopedKey);
                break;

            // Organization manager (from attributeValueOrganization)
            case CheckPolicyListener::attributeValueControllerString . "patchAttributevalueorganizationAction":
            case CheckPolicyListener::attributeValueControllerString . "putAttributevalueorganizationAction":
            case CheckPolicyListener::attributeValueControllerString . "deleteAttributevalueorganizationAction":
            case CheckPolicyListener::attributeValueControllerString . "putAttributevalueorganizationServiceAction":
            case CheckPolicyListener::attributeValueControllerString . "deleteAttributevalueorganizationServiceAction":
                $avo = $this->eh->get('AttributeValueOrganization', $request->attributes->get('id'), $_controller);
                $this->idsToLog['id'] = $request->attributes->get('id');

                return $this->isManagerOfOrganization($avo->getOrganization(), $p, $_controller, $scopedKey);
                break;

            // Organization member (from id)
            case CheckPolicyListener::newsControllerString . "cgetOrganizationsNewsAction":
                $this->idsToLog['id'] = $request->attributes->get('id');
                return $this->isMemberOfOrganization($request->attributes->get('id'), $p, $_controller, $scopedKey);
                break;

            // Organization member (from role)
            case CheckPolicyListener::roleControllerString . "getRoleAction":
            case CheckPolicyListener::roleControllerString . "cgetRoleEntitlementsAction":
            case CheckPolicyListener::roleControllerString . "cgetRolePrincipalsAction":
                $r = $this->eh->get('Role', $request->attributes->get('id'), $_controller);
                $this->idsToLog['id'] = $request->attributes->get('id');
                $o = $r->getOrganization();

                return $this->isMemberOfOrganization($o, $p, $_controller, $scopedKey);
                break;

            // Organization member (from attributeValueOrganization)
            case CheckPolicyListener::attributeValueControllerString . "getAttributevalueorganizationAction":
            case CheckPolicyListener::attributeValueControllerString . "cgetAttributevalueorganizationsServicesAction":
            case CheckPolicyListener::attributeValueControllerString . "getAttributevalueorganizationServiceAction":
                $this->idsToLog['id'] = $request->attributes->get('id');
                $avo = $this->eh->get('AttributeValueOrganization', $request->attributes->get('id'), $_controller);
                $o = $avo->getOrganization();

                return $this->isMemberOfOrganization($o, $p, $_controller, $scopedKey);
                break;

            // Self or admin (AttributeValuePrincipal)
            case CheckPolicyListener::attributeValueControllerString . "getAttributevalueprincipalAction":
            case CheckPolicyListener::attributeValueControllerString . "putAttributevalueprincipalAction":
            case CheckPolicyListener::attributeValueControllerString . "patchAttributevalueprincipalAction":
            case CheckPolicyListener::attributeValueControllerString . "deleteAttributevalueprincipalAction":
            case CheckPolicyListener::attributeValueControllerString . "cgetAttributevalueprincipalsServicesAction":
            case CheckPolicyListener::attributeValueControllerString . "getAttributevalueprincipalsServiceAction":
            case CheckPolicyListener::attributeValueControllerString . "putAttributevalueprincipalsServiceAction":
            case CheckPolicyListener::attributeValueControllerString . "deleteAttributevalueprincipalServiceAction":
                $this->idsToLog['id'] = $request->attributes->get('id');
                $avp = $this->eh->get('AttributeValuePrincipal', $request->attributes->get('id'), $_controller);

                return ($avp->getPrincipal() === $p);
                break;

            // Self or admin (from request)
            case CheckPolicyListener::attributeValueControllerString . "postAttributevalueprincipalAction":
                if ($request->request->has('principal')) {
                    $this->idsToLog['principal'] = $request->request->get('principal');

                    return ($request->request->get('principal') === $p->getId());
                } else
                    return true; // Will default to self
                break;

            // Self or admin (from id)
            case CheckPolicyListener::principalControllerString . "putPrincipalAction":
                $this->idsToLog['id'] = $request->attributes->get('id');

                 return $request->attributes->get('id') === $p->getId();
                break;

            // Self or service manager (from service id)
            case CheckPolicyListener::serviceChildControllerString . "deleteManagerAction":
                if ($request->attributes->get('pid') === $p->getId()) {
                    return true;
                } else {
                    $this->idsToLog['id'] = $request->attributes->get('id');
                    return $this->isManagerOfService($request->attributes->get('id'), $p, $_controller, $scopedKey);
                }
                break;


            // Self (from consent)
            case CheckPolicyListener::consentControllerString . "getAction":
            case CheckPolicyListener::consentControllerString . "putAction":
            case CheckPolicyListener::consentControllerString . "patchAction":
                $c = $this->eh->get('Consent', $request->attributes->get('id'), $_controller);
                $this->idsToLog['id'] = $request->attributes->get('id');

                return ($c->getPrincipal() === $p);
                break;

            //Self (from request)
            case CheckPolicyListener::consentControllerString . "postAction":
                if ($request->request->has('principal')) {
                    $this->idsToLog['principal'] = $request->request->get('principal');
                    return ($request->request->get('principal') === $p->getId());
                } else
                    return true; // Will default to self
                break;


            // service & organization manager (from invitation request)
            case CheckPolicyListener::invitationControllerString . "postInvitationAction":
                if ($request->request->has('service')) {
                    $this->idsToLog['service'] = $request->request->get('service');
                    return $this->isManagerOfService($request->request->get('service'), $p, $_controller, $scopedKey);
                } else {
                    if ($request->request->has('organization')) {
                        $this->idsToLog['organization'] = $request->request->get('organization');
                        return $this->isManagerOfOrganization($request->request->get('organization'), $p, $_controller, $scopedKey);
                    } else
                        return true; // Let validation handle it, it will fail anyway.
                }
                break;

            // service & organization manager (from invitation)
            case CheckPolicyListener::invitationControllerString . "getInvitationAction":
            case CheckPolicyListener::invitationControllerString . "getInvitationResendAction":
            case CheckPolicyListener::invitationControllerString . "putInvitationAction":
            case CheckPolicyListener::invitationControllerString . "patchInvitationAction":
            case CheckPolicyListener::invitationControllerString . "deleteInvitationAction":
                $i = $this->eh->get('Invitation', $request->attributes->get('id'), $_controller);
                $this->idsToLog['id'] = $request->attributes->get('id');
                $s = $i->getService();
                $o = $i->getOrganization();
                if ($s instanceof Service) {
                    return $this->isManagerOfService($s, $p, $_controller, $scopedKey);
                }
                if ($o instanceof Organization) {
                    return $this->isManagerOfOrganization($o, $p, $_controller, $scopedKey);
                }

                return false; // This shouldn't happen, but lock them out, just to be sure.
                break;

            // service & organization manager (from organization and entitlementPack)
            case CheckPolicyListener::organizationChildControllerString . "deleteEntitlementpacksAction":
                $o = $this->eh->get('Organization', $request->attributes->get('id'), $_controller);
                $ep = $this->eh->get('EntitlementPack', $request->attributes->get('epid'), $_controller);
                $this->idsToLog['id'] = $request->attributes->get('id');
                $this->idsToLog['epid'] = $request->attributes->get('epid');
                $s = $ep->getService();

                return ($this->isManagerOfOrganization($o, $p, $_controller, $scopedKey) || $this->isManagerOfService($s, $p, $_controller, $scopedKey));
                break;

            // service manager (from entitlementPack [epid])
            case CheckPolicyListener::organizationChildControllerString . "putEntitlementpacksAcceptAction":
                $ep = $this->eh->get('EntitlementPack', $request->attributes->get('epid'), $_controller);
                $this->idsToLog['epid'] = $request->attributes->get('epid');
                $s = $ep->getService();

                return $this->isManagerOfService($s, $p, $_controller, $scopedKey);
                break;

            // Organization member & related service manager (from organization)
            case CheckPolicyListener::organizationControllerString . "getAction":
                $o = $this->eh->get('Organization', $request->attributes->get('id'), $_controller);
                $this->idsToLog['id'] = $request->attributes->get('id');
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

                return ($this->isMemberOfOrganization($o, $p, $_controller, $scopedKey) || in_array($p, $sManagers, true));
                break;

            // Service manager or related organization member or
            // ANYONE if the service has public attributes or entitlement packs
            case CheckPolicyListener::serviceControllerString . "getAction":
                $s = $this->eh->get('Service', $request->attributes->get('id'), $_controller);
                $this->idsToLog['id'] = $request->attributes->get('id');
                $ss = $this->em->getRepository('HexaaStorageBundle:Service')->findAllByRelatedPrincipal($p);

                $countPublicEntPacks = $this->em->createQueryBuilder()
                    ->select('COUNT(ep.id)')
                    ->from("HexaaStorageBundle:EntitlementPack", "ep")
                    ->where("ep.service = :s")
                    ->andWhere("ep.type='public'")
                    ->setParameter(":s", $s)
                    ->getQuery()
                    ->getSingleScalarResult();
                $countPublicAttrSpecs = $this->em->createQueryBuilder()
                    ->select('COUNT(attrspec.id)')
                    ->from("HexaaStorageBundle:ServiceAttributeSpec", "attrspec")
                    ->where("attrspec.service = :s")
                    ->andWhere("attrspec.isPublic=true")
                    ->setParameter(":s", $s)
                    ->getQuery()
                    ->getSingleScalarResult();

                if (($countPublicEntPacks + $countPublicAttrSpecs)>0) {
                    return true; // Has public attribute specs or public entitlement packs so it must be available
                } else {
                    return ($this->isManagerOfService($s, $p, $_controller, $scopedKey) || in_array($s, $ss, true));
                }
                break;

            // Admin, service manager, organization manager depending on parameters of message
            case CheckPolicyListener::globalControllerString . "putMessageAction":
                return $this->getPermissionFromMessageCall($p, $_controller, $request, $scopedKey);
                break;


            // No special permission required
            case CheckPolicyListener::attributeSpecControllerString . "cgetAction":
            case CheckPolicyListener::attributeSpecControllerString . "getAction":
            case CheckPolicyListener::attributeSpecControllerString . "cgetServicesAction":
            case CheckPolicyListener::consentControllerString . "cgetAction":
            case CheckPolicyListener::consentControllerString . "getServiceAction":
            case CheckPolicyListener::entitlementPackControllerString . "getEntitlementpackAction":
            case CheckPolicyListener::entitlementPackControllerString . "cgetEntitlementpacksPublicAction":
            case CheckPolicyListener::entitlementPackEntitlementControllerString . "cgetEntitlementsAction":
            case CheckPolicyListener::globalControllerString . "cgetEntityidsAction":
            case CheckPolicyListener::globalControllerString . "cgetTagsAction":
            case CheckPolicyListener::globalControllerString . "getPropertiesAction":
            case CheckPolicyListener::invitationControllerString . "getInvitationAcceptEmailAction":
            case CheckPolicyListener::invitationControllerString . "getInvitationAcceptTokenAction":
            case CheckPolicyListener::invitationControllerString . "getInvitationRejectEmailAction":
            case CheckPolicyListener::newsControllerString . "getPrincipalNewsAction":
            case CheckPolicyListener::organizationChildControllerString . "cgetManagersAction":
            case CheckPolicyListener::organizationChildControllerString . "getManagerCountAction":
            case CheckPolicyListener::organizationChildControllerString . "getMemberCountAction":
            case CheckPolicyListener::organizationChildControllerString . "cgetMembersAction":
            case CheckPolicyListener::organizationChildControllerString . "cgetEntitlementsAction":
            case CheckPolicyListener::organizationChildControllerString . "cgetEntitlementpacksAction":
            case CheckPolicyListener::organizationChildControllerString . "cgetRolesAction":
            case CheckPolicyListener::organizationControllerString . "cgetAction":
            case CheckPolicyListener::organizationControllerString . "postAction":
            case CheckPolicyListener::principalControllerString . "getPrincipalIsadminAction":
            case CheckPolicyListener::principalControllerString . "getPrincipalSelfAction":
            case CheckPolicyListener::principalControllerString . "getPrincipalIdAction":
            case CheckPolicyListener::principalControllerString . "getPrincipalFedidAction":
            case CheckPolicyListener::principalControllerString . "cgetPrincipalInvitationsAction":
            case CheckPolicyListener::principalControllerString . "cgetPrincipalAttributespecsAction":
            case CheckPolicyListener::principalControllerString . "cgetPrincipalAttributespecsAttributevalueprincipalsAction":
            case CheckPolicyListener::principalControllerString . "cgetPrincipalAttributevalueprincipalAction":
            case CheckPolicyListener::principalControllerString . "cgetManagerServicesAction":
            case CheckPolicyListener::principalControllerString . "cgetManagerOrganizationsAction":
            case CheckPolicyListener::principalControllerString . "cgetMemberOrganizationsAction":
            case CheckPolicyListener::principalControllerString . "cgetPrincipalEntitlementsAction":
            case CheckPolicyListener::principalControllerString . "cgetPrincipalEntitlementpackRelatedAction":
            case CheckPolicyListener::principalControllerString . "cgetPrincipalServicesRelatedAction":
            case CheckPolicyListener::principalControllerString . "cgetPrincipalRolesAction":
            case CheckPolicyListener::principalControllerString . "deletePrincipalAction":
            case CheckPolicyListener::serviceChildControllerString . "cgetManagersAction":
            case CheckPolicyListener::serviceChildControllerString . "getManagerCountAction":
            case CheckPolicyListener::serviceChildControllerString . "cgetAttributespecsAction":
            case CheckPolicyListener::serviceChildControllerString . "cgetEntitlementpackRequestsAction":
            case CheckPolicyListener::serviceChildControllerString . "cgetOrganizationsAction":
            case CheckPolicyListener::serviceChildControllerString . "cgetEntitlementsAction":
            case CheckPolicyListener::serviceChildControllerString . "cgetEntitlementpacksAction":
            case CheckPolicyListener::serviceControllerString . "cgetAction":
            case CheckPolicyListener::serviceControllerString . "postAction":
            case CheckPolicyListener::serviceControllerString . "putEnableAction":
                return true;
                break;

            // Others
            default:
                return false;
        }
    }

    private function checkServiceInSecurityDomain(Service $service, $scopedKey) {
        $sd = $this->em->createQueryBuilder()
            ->select('COUNT(sd.id)')
            ->from('HexaaStorageBundle:SecurityDomain', 'sd')
            ->where('sd.scopedKey = :sk')
            ->andWhere(":s MEMBER OF sd.services")
            ->setParameters(array(":s" => $service, ":sk" => $scopedKey))
            ->getQuery()
            ->getSingleScalarResult();

        return ($sd >= 1);
    }

    private function checkOrganizationInSecurityDomain(Organization $organization, $scopedKey) {
        $sd = $this->em->createQueryBuilder()
            ->select('COUNT(sd.id)')
            ->from('HexaaStorageBundle:SecurityDomain', 'sd')
            ->where('sd.scopedKey = :sk')
            ->andWhere(":o MEMBER OF sd.organizations")
            ->setParameters(array(":o" => $organization, ":sk" => $scopedKey))
            ->getQuery()
            ->getSingleScalarResult();

        return ($sd >= 1);
    }

    private function accessDeniedError(Principal $p, $_controller) {
        $ids = "";
        foreach($this->idsToLog as $idName=>$value){
            $ids = $ids . ", " . $idName . ": " . $value;
        }
        $this->errorlog->error("User " . $p->getFedid() . " has insufficient permissions in " . $_controller . $ids);
        throw new HttpException(403, "User " . $p->getFedid() . " has insufficient permissions in " . $_controller . $ids);
    }

    private function isManagerOfService($id, Principal $p, $_controller, $scopedKey) {
        if ($id instanceof Service) {
            $s = $id;
        } else {
            $s = $this->eh->get('Service', $id, $_controller);
        }

        return ($s->hasManager($p) || $this->checkServiceInSecurityDomain($s, $scopedKey));
    }

    private function isManagerOfOrganization($id, Principal $p, $_controller, $scopedKey) {
        if ($id instanceof Organization) {
            $o = $id;
        } else {
            $o = $this->eh->get('Organization', $id, $_controller);
        }

        return ($o->hasManager($p) || $this->checkOrganizationInSecurityDomain($o, $scopedKey));
    }

    private function isMemberOfOrganization($id, Principal $p, $_controller, $scopedKey) {
        if ($id instanceof Organization) {
            $o = $id;
        } else {
            $o = $this->eh->get('Organization', $id, $_controller);
        }

        return ($o->hasPrincipal($p) || $this->checkOrganizationInSecurityDomain($o, $scopedKey));
    }

    private function isAdmin(Principal $p, Request $request) {
        if ($request->query->has("admin") && ($request->query->get("admin") === true || $request->query->get('admin') === "true")) {
            $isAdmin = in_array($p->getFedid(), $this->admins);
            if ($isAdmin) {
                $request->attributes->set("_security.level","admin");
            }
            return $isAdmin;
        } else {
            return false;
        }
    }

    private function getPermissionFromMessageCall(Principal $p, $_controller, Request $request, $scopedKey) {
        if ($request->request->has("target") && $request->request->get('target') !== null){
            $this->idsToLog['target'] = $request->request->get('target');
            $target = $request->request->get('target');
            switch($target){
                case "admin":
                    return $this->isAdmin($p, $request);
                    break;
                case "manager":
                    if ($request->request->has('service') && $request->request->get('service') !== null) {
                        $this->idsToLog['service'] = $request->request->get('service');

                        return $this->isManagerOfService($request->request->get('service'), $p, $_controller, $scopedKey);
                    }
                    if ($request->request->has('organization') && $request->request->get('organization') !== null) {
                        $this->idsToLog['organization'] = $request->request->get('organization');

                        return $this->isManagerOfOrganization($request->request->get('organization'), $p, $_controller, $scopedKey);
                    }
                    break;
                case "user":
                    if ($request->request->has('organization') && $request->request->get('organization') !== null) {
                        $this->idsToLog['organization'] = $request->request->get('organization');

                        return $this->isManagerOfOrganization($request->request->get('organization'), $p, $_controller, $scopedKey);
                    }
                    break;
                default:
                    // Return true as validation will provide sane error message
                    return true;
            }
        } else
            // Return true as validation will provide sane error message
            return true;
        // Should not happen, but return false just in case
        return false;
    }

}
