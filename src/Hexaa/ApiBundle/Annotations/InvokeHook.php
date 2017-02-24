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
     * @Enum({
     *     'attribute_change',
     *     'user_added',
     *     'user_removed'
     *     })
     */
    public $types;

    /**
     * @var string
     * @Enum({
     *     'AttributeSpec',
     *     'AttributeValueOrganization',
     *     'AttributeValuePrincipal',
     *     'Consent',
     *     'Entitlement',
     *     'EntitlementPack',
     *     'Organization',
     *     'OrganizationEntitlementPack',
     *     'Principal',
     *     'Role',
     *     'RolePrincipal',
     *     'Service',
     *     'ServiceAttributeSpec'
     *     })
     * @Required
     */
    public $entity;

    /**
     * @var string
     * @Enum({
     *     'id',
     *     'eid',
     *     'epid',
     *     'sid',
     *     'service',
     *     'token'
     *     })
     */
    public $id;

    /**
     * @var string
     * @Enum({
     *     'attributes',
     *     'request',
     *     'principal',
     *     'in_flight,
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