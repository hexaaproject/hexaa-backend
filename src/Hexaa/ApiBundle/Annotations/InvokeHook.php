<?php
/**
 * Created by solazs
 * Date: 8/19/15
 * Time: 10:54 AM
 */

namespace Hexaa\ApiBundle\Annotations;

use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;


/**
 * @Annotation
 * @Target("METHOD")
 */
class InvokeHook
{
    /**
     * @var array<string>
     */
    public $types;

    /**
     * @var string
     * @Enum({
     *     "AttributeSpec",
     *     "AttributeValueOrganization",
     *     "AttributeValuePrincipal",
     *     "Consent",
     *     "Entitlement",
     *     "EntitlementPack",
     *     "Invitation",
     *     "Organization",
     *     "Principal",
     *     "Role",
     *     "Service",
     *     "Link"
     *     })
     * @Required
     */
    public $entity;

    /**
     * @var string
     * @Enum({
     *     "id",
     *     "eid",
     *     "epid",
     *     "sid",
     *     "service",
     *     "token",
     *     "fedid"
     *     })
     */
    public $id;

    /**
     * @var string
     * @Enum({
     *     "attributes",
     *     "request",
     *     "principal",
     *     "link"
     *     })
     * @Required
     */
    public $source;

    public function getInfo()
    {
        return array(
          'entity' => $this->entity,
          'id'     => $this->id,
          'source' => $this->source,
        );
    }
}