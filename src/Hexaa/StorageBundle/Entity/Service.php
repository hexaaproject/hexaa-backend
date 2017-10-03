<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\Type;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Service
 *
 * @ORM\Table(
 *   name="service",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="name", columns={"name"})
 *   }
 * )
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\ServiceRepository")
 * @UniqueEntity("name")
 * @ORM\HasLifecycleCallbacks
 */
class Service
{

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getLogoPath")
     * @Groups({"normal", "expanded"})
     */
    private $logoPath = null;
    /**
     * @ORM\ManyToMany(targetEntity="Principal", inversedBy="services")
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $managers;
    /**
     *
     * @var File
     * @Exclude
     */
    private $tempFile;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "3",
     *      max = "255",
     *      minMessage = "Minimum name length: 3 characters",
     *      maxMessage = "Maximum name length: 255 characters"
     * )
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="ServiceAttributeSpec", mappedBy="service", cascade={"persist"}, orphanRemoval=true)
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $attributeSpecs;

    /**
     * @ORM\OneToMany(targetEntity="Hook", mappedBy="service", cascade={"persist"}, orphanRemoval=true)
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $hooks;

    /**
     * @var string
     *
     * @ORM\Column(name="hookKey", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = "128",
     *      max = "255",
     *      minMessage = "Minimum name length: 128 characters",
     *      maxMessage = "Maximum name length: 255 characters"
     * )
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $hookKey;

    /**
     * @var string
     *
     * @ORM\Column(name="entityid", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @HexaaAssert\ValidEntityid()
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $entityid;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="org_name", type="string", length=255, nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $orgName;

    /**
     * @var string
     *
     * @ORM\Column(name="enable_token", type="string", length=255, nullable=true)
     * @Exclude
     */
    private $enableToken;

    /**
     * @var string
     *
     * @ORM\Column(name="org_short_name", type="string", length=255, nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $orgShortName;

    /**
     * @var string
     *
     * @ORM\Column(name="org_url", type="string", length=255, nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $orgUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="org_description", type="text", nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $orgDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="priv_url", type="string", length=255, nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $privUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="priv_description", type="text", nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $privDescription;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $isEnabled = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"minimal", "normal", "expanded"})
     * @Accessor(getter="getId")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_loa", type="bigint", nullable=true)
     * @Groups({"minimal", "normal", "expanded"})
     */
    private $minLoa = 0;
    /**
     * @Assert\Image(
     *     maxSize="6000000",
     *     minWidth = 150,
     *     maxWidth = 400,
     *     minHeight = 150,
     *     maxHeight = 400
     * )
     * @Exclude
     */
    private $logo = null;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="privacy_policy_set_at", type="datetime", nullable=true)
     * @Groups({"normal", "expanded"})
     */
    private $privacyPolicySetAt;
    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Tag", inversedBy="services")
     * @ORM\JoinTable(
     *   name="service_tag",
     *   joinColumns={@ORM\JoinColumn(name="service_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="name")}
     * )
     * @Groups({"minimal", "normal", "extended"})
     **/
    private $tags;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\Link", mappedBy="service", cascade={"persist"})
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $links;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\Invitation", mappedBy="service")
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $invitations;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\Entitlement", mappedBy="service")
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $entitlements;
    /**
     * @ORM\OneToMany(targetEntity="Hexaa\StorageBundle\Entity\EntitlementPack", mappedBy="service")
     * @Groups({"expanded"})
     * @MaxDepth(1)
     */
    private $entitlementPacks;

    /**
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\AttributeValuePrincipal", mappedBy="services")
     * @ORM\JoinTable(name="service_attribute_value_principal")
     *
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $attributeValuePrincipals;

    /**
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\AttributeValueOrganization", mappedBy="services")
     * @ORM\JoinTable(name="service_attribute_value_organization")
     *
     * @Groups({"expanded"})
     * @MaxDepth(2)
     */
    private $attributeValueOrganizations;
    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\SecurityDomain", inversedBy="services")
     * @ORM\JoinTable(name="service_security_domain")
     * @Exclude()
     **/
    private $securityDomains;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @Groups({"normal", "expanded"})
     */
    private $createdAt;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     * @Groups({"normal", "expanded"})
     */
    private $updatedAt;

    public function __construct()
    {
        $this->managers = new ArrayCollection();
        $this->attributeSpecs = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->securityDomains = new ArrayCollection();
        $this->hooks = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->attributeValuePrincipals = new ArrayCollection();
        $this->attributeValueOrganizations = new ArrayCollection();
        $this->entitlements = new ArrayCollection();
        $this->entitlementPacks = new ArrayCollection();
        $this->generateEnableToken();
        $this->generateHookKey();
        $this->isEnabled = false;
    }

    /**
     * Generate enableToken
     *
     * @return Service
     */
    public function generateEnableToken()
    {
        try {
            $uuid = Uuid::uuid4();
        } catch (UnsatisfiedDependencyException $e) {
            // Some dependency was not met. Either the method cannot be called on a
            // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
            $uuid = uniqid();
        }

        $this->enableToken = $uuid;

        return $this;
    }

    /**
     * Generate enableToken
     *
     * @return Service
     */
    public function generateHookKey()
    {
        try {
            $uuid = Uuid::uuid4();
        } catch (UnsatisfiedDependencyException $e) {
            // Some dependency was not met. Either the method cannot be called on a
            // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
            $uuid = uniqid();
        }

        $this->hookKey = hash("sha512", $uuid);

        return $this;
    }

    /**
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Service
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->getLogo()) {
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->logoPath = $filename.'.'.$this->getLogo()->guessExtension();
        }
    }

    /**
     * Get logo.
     *
     * @return UploadedFile
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Sets logo.
     *
     * @param UploadedFile $file
     */
    public function setLogo(UploadedFile $file = null)
    {
        $this->logo = $file;
        // check if we have an old image path
        if (isset($this->logoPath)) {
            // store the old name to delete after the update
            $this->tempFile = $this->logoPath;
            $this->logoPath = null;
        } else {
            $this->logoPath = 'initial';
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->getLogo()) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->getLogo()->move($this->getUploadRootDir(), $this->logoPath);

        // check if we have an old image
        if (isset($this->tempFile)) {
            // delete the old image
            unlink($this->getUploadRootDir().'/'.$this->tempFile);
            // clear the temp image path
            $this->tempFile = null;
        }
        $this->logo = null;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/service_logos';
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

    public function getAbsolutePath()
    {
        return null === $this->logoPath ? null : $this->getUploadRootDir().'/'.$this->logoPath;
    }

    /**
     * Get logo path
     *
     * @return string
     */
    public function getLogoPath()
    {
        if ($this->logoPath == null) {
            return null;
        } else {
            return $this->getUploadDir().'/'.$this->logoPath;
        }
    }

    /**
     * Set logoPath
     *
     * @param string $logoPath
     * @return Service
     */
    public function setLogoPath($logoPath)
    {

        /*
         * DELIBERATELY DO NOTHING
         * function is here only so that the Symfony won't generate it again.
         */


        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Service
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHooks()
    {
        return $this->hooks;
    }

    /**
     * @param mixed $hooks
     */
    public function setHooks($hooks)
    {
        $this->hooks = $hooks;
    }

    /**
     * Get enableToken
     *
     * @return string
     */
    public function getEnableToken()
    {
        return $this->enableToken;
    }

    /**
     * Set enableToken
     *
     * @param string $enableToken
     * @return Service
     */
    public function setEnableToken($enableToken)
    {
        /*
         * DELIBERATELY DO NOTHING
         * function is here only so that the Symfony won't generate it again.
         */

        return $this;
    }

    /**
     * Get minLoa
     *
     * @return integer
     */
    public function getMinLoa()
    {
        return $this->minLoa;
    }

    /**
     * Set minLoa
     *
     * @param integer $minLoa
     * @return Service
     */
    public function setMinLoa($minLoa)
    {
        $this->minLoa = $minLoa;

        return $this;
    }

    /**
     * Get entityid
     *
     * @return string
     */
    public function getEntityid()
    {
        return $this->entityid;
    }

    /**
     * Set entityid
     *
     * @param string $entityid
     * @return Service
     */
    public function setEntityid($entityid)
    {
        $this->entityid = $entityid;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Service
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Service
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Service
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get id
     * @Type("integer")
     *
     * @return integer
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * Has manager
     *
     * @param Principal $manager
     *
     * @return boolean
     */
    public function hasManager(Principal $manager)
    {
        return $this->managers->contains($manager);
    }

    /**
     * Add managers
     *
     * @param Principal $managers
     * @return Service
     */
    public function addManager(Principal $managers)
    {
        $this->managers[] = $managers;

        return $this;
    }

    /**
     * Remove managers
     *
     * @param Principal $managers
     */
    public function removeManager(Principal $managers)
    {
        $this->managers->removeElement($managers);
    }

    /**
     * Get managers
     *
     * @return ArrayCollection
     */
    public function getManagers()
    {
        return $this->managers;
    }

    public function getWebPath()
    {
        return null === $this->logoPath ? null : $this->getUploadDir().'/'.$this->logoPath;
    }

    /**
     * Add AttributeSpecs
     *
     * @param ServiceAttributeSpec $attributeSpecs
     * @return Service
     */
    public function addAttributeSpec(ServiceAttributeSpec $attributeSpecs)
    {
        $this->attributeSpecs[] = $attributeSpecs;

        if ($attributeSpecs->getService() !== $this) {
            $attributeSpecs->setService($this);
        }

        return $this;
    }

    /**
     * Remove AttributeSpecs
     *
     * @param ServiceAttributeSpec $attributeSpecs
     */
    public function removeAttributeSpec(ServiceAttributeSpec $attributeSpecs)
    {

        $attributeSpecs->setService(null);
        $this->attributeSpecs->removeElement($attributeSpecs);
    }

    /**
     * Get attributeSpecs
     *
     * @return ArrayCollection
     */
    public function getAttributeSpecs()
    {
        return $this->attributeSpecs;
    }

    /**
     * Has attributeSpec
     *
     * @param ServiceAttributeSpec $attributeSpec
     *
     * @return boolean
     */
    public function hasAttributeSpec(ServiceAttributeSpec $attributeSpec)
    {
        return $this->attributeSpecs->contains($attributeSpec);
    }

    /**
     * Get orgName
     *
     * @return string
     */
    public function getOrgName()
    {
        return $this->orgName;
    }

    /**
     * Set orgName
     *
     * @param string $orgName
     * @return Service
     */
    public function setOrgName($orgName)
    {
        $this->orgName = $orgName;

        return $this;
    }

    /**
     * Get orgShortName
     *
     * @return string
     */
    public function getOrgShortName()
    {
        return $this->orgShortName;
    }

    /**
     * Set orgShortName
     *
     * @param string $orgShortName
     * @return Service
     */
    public function setOrgShortName($orgShortName)
    {
        $this->orgShortName = $orgShortName;

        return $this;
    }

    /**
     * Get orgUrl
     *
     * @return string
     */
    public function getOrgUrl()
    {
        return $this->orgUrl;
    }

    /**
     * Set orgUrl
     *
     * @param string $orgUrl
     * @return Service
     */
    public function setOrgUrl($orgUrl)
    {
        $this->orgUrl = $orgUrl;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return boolean
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     * @return Service
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get orgDescription
     *
     * @return string
     */
    public function getOrgDescription()
    {
        return $this->orgDescription;
    }

    /**
     * Set orgDescription
     *
     * @param string $orgDescription
     * @return Service
     */
    public function setOrgDescription($orgDescription)
    {
        $this->orgDescription = $orgDescription;

        return $this;
    }

    /**
     * Get privUrl
     *
     * @return string
     */
    public function getPrivUrl()
    {
        return $this->privUrl;
    }

    /**
     * Set privUrl
     *
     * @param string $privUrl
     * @return Service
     */
    public function setPrivUrl($privUrl)
    {
        $this->privUrl = $privUrl;

        $this->setPrivacyPolicySetAt(new \DateTime('now'));

        return $this;
    }

    /**
     * Get privDescription
     *
     * @return string
     */
    public function getPrivDescription()
    {
        return $this->privDescription;
    }

    /**
     * Set privDescription
     *
     * @param string $privDescription
     * @return Service
     */
    public function setPrivDescription($privDescription)
    {
        $this->privDescription = $privDescription;

        $this->setPrivacyPolicySetAt(new \DateTime('now'));

        return $this;
    }

    /**
     * Get privacyPolicySetAt
     *
     * @return \DateTime
     */
    public function getPrivacyPolicySetAt()
    {
        return $this->privacyPolicySetAt;
    }

    /**
     * Set privacyPolicySetAt
     *
     * @param \DateTime $privacyPolicySetAt
     * @return Service
     */
    public function setPrivacyPolicySetAt($privacyPolicySetAt)
    {
        $this->privacyPolicySetAt = $privacyPolicySetAt;

        return $this;
    }

    /**
     * Add tags
     *
     * @param Tag $tag
     * @return Service
     */
    public function addTag(Tag $tag)
    {
        if (!$tag->hasService($this)) {
            $tag->addService($this);
        }
        $this->tags->add($tag);

        return $this;
    }

    /**
     * Remove tags
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        if ($tag->hasService($this)) {
            $tag->removeService($this);
        }
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Has tag
     *
     * @param Tag $tag
     * @return boolean
     */
    public function hasTag(Tag $tag)
    {
        return $this->tags->contains($tag);
    }

    /**
     * Add securityDomains
     *
     * @param \Hexaa\StorageBundle\Entity\SecurityDomain $securityDomain
     * @return Service
     */
    public function addSecurityDomain(SecurityDomain $securityDomain)
    {
        $this->securityDomains->add($securityDomain);


        return $this;
    }

    /**
     * Remove securityDomains
     *
     * @param \Hexaa\StorageBundle\Entity\SecurityDomain $securityDomain
     */
    public function removeSecurityDomain(SecurityDomain $securityDomain)
    {
        $this->securityDomains->removeElement($securityDomain);
    }

    /**
     * Get securityDomains
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSecurityDomains()
    {
        return $this->securityDomains;
    }

    /**
     * Has SecurityDomain
     *
     * @param SecurityDomain $securityDomain
     * @return boolean
     */
    public function hasSecurityDomain(SecurityDomain $securityDomain)
    {
        return $this->tags->contains($securityDomain);
    }

    /**
     * @return string
     */
    public function getHookKey()
    {
        return $this->hookKey;
    }

    /**
     * @param string $hookKey
     */
    public function setHookKey($hookKey)
    {
        $this->hookKey = $hookKey;
    }

    /**
     * Add links
     *
     * @param Link $link
     * @return Service
     */
    public function addLink(Link $link)
    {
        $this->links[] = $link;

        if ($link->getService() !== $this) {
            $link->setService($this);
        }

        return $this;
    }

    /**
     * Remove link
     *
     * @param Link $link
     */
    public function removeLink(Link $link)
    {

        $link->setService(null);
        $this->links->removeElement($link);
    }

    /**
     * Get links
     *
     * @return ArrayCollection
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Has EntitlementPack
     *
     * @param Link $link
     *
     * @return boolean
     */
    public function hasLink(Link $link)
    {
        return $this->links->contains($link);
    }


    /**
     * Clear links
     *
     */
    public function clearLinks()
    {
        $this->links->clear();
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Add hooks
     *
     * @param \Hexaa\StorageBundle\Entity\Hook $hooks
     * @return Service
     */
    public function addHook(\Hexaa\StorageBundle\Entity\Hook $hooks)
    {
        $this->hooks[] = $hooks;

        return $this;
    }

    /**
     * Remove hooks
     *
     * @param \Hexaa\StorageBundle\Entity\Hook $hooks
     */
    public function removeHook(\Hexaa\StorageBundle\Entity\Hook $hooks)
    {
        $this->hooks->removeElement($hooks);
    }

    /**
     * Add invitations
     *
     * @param \Hexaa\StorageBundle\Entity\Invitation $invitations
     * @return Service
     */
    public function addInvitation(\Hexaa\StorageBundle\Entity\Invitation $invitations)
    {
        $this->invitations[] = $invitations;

        return $this;
    }

    /**
     * Remove invitations
     *
     * @param \Hexaa\StorageBundle\Entity\Invitation $invitations
     */
    public function removeInvitation(\Hexaa\StorageBundle\Entity\Invitation $invitations)
    {
        $this->invitations->removeElement($invitations);
    }

    /**
     * Get invitations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInvitations()
    {
        return $this->invitations;
    }

    /**
     * Add entitlements
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlements
     * @return Service
     */
    public function addEntitlement(\Hexaa\StorageBundle\Entity\Entitlement $entitlements)
    {
        $this->entitlements[] = $entitlements;

        return $this;
    }

    /**
     * Remove entitlements
     *
     * @param \Hexaa\StorageBundle\Entity\Entitlement $entitlements
     */
    public function removeEntitlement(\Hexaa\StorageBundle\Entity\Entitlement $entitlements)
    {
        $this->entitlements->removeElement($entitlements);
    }

    /**
     * Get entitlements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEntitlements()
    {
        return $this->entitlements;
    }

    /**
     * Add entitlementPacks
     *
     * @param \Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks
     * @return Service
     */
    public function addEntitlementPack(\Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks)
    {
        $this->entitlementPacks[] = $entitlementPacks;

        return $this;
    }

    /**
     * Remove entitlementPacks
     *
     * @param \Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks
     */
    public function removeEntitlementPack(\Hexaa\StorageBundle\Entity\EntitlementPack $entitlementPacks)
    {
        $this->entitlementPacks->removeElement($entitlementPacks);
    }

    /**
     * Get entitlementPacks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEntitlementPacks()
    {
        return $this->entitlementPacks;
    }

    /**
     * Add attributeValuePrincipals
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipals
     * @return Service
     */
    public function addAttributeValuePrincipal(\Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipals)
    {
        $this->attributeValuePrincipals[] = $attributeValuePrincipals;

        return $this;
    }

    /**
     * Remove attributeValuePrincipals
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipals
     */
    public function removeAttributeValuePrincipal(\Hexaa\StorageBundle\Entity\AttributeValuePrincipal $attributeValuePrincipals)
    {
        $this->attributeValuePrincipals->removeElement($attributeValuePrincipals);
    }

    /**
     * Get attributeValuePrincipals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributeValuePrincipals()
    {
        return $this->attributeValuePrincipals;
    }

    /**
     * Add attributeValueOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
     * @return Service
     */
    public function addAttributeValueOrganization(
      \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
    ) {
        $this->attributeValueOrganizations[] = $attributeValueOrganizations;

        return $this;
    }

    /**
     * Remove attributeValueOrganizations
     *
     * @param \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
     */
    public function removeAttributeValueOrganization(
      \Hexaa\StorageBundle\Entity\AttributeValueOrganization $attributeValueOrganizations
    ) {
        $this->attributeValueOrganizations->removeElement($attributeValueOrganizations);
    }

    /**
     * Get attributeValueOrganizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributeValueOrganizations()
    {
        return $this->attributeValueOrganizations;
    }
}
