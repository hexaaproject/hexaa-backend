<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

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
class Service {

    /**
     * @ORM\ManyToMany(targetEntity="Principal")
     * @Groups({"expanded"})
     */
    private $managers;
    
    /**
     *
     * @var File
     * @Exclude
     */
    private $tempFile;

    public function __construct() {
        $this->managers = new ArrayCollection();
        $this->attributeSpecs = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->securityDomains = new ArrayCollection();
        $this->generateEnableToken();
        $this->isEnabled = false;
    }

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
     * @Assert\Valid(traverse=true)
     * @Groups({"expanded"})
     */
    private $attributeSpecs;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getLogoPath")
     * @Groups({"normal", "expanded"})
     */
    public $logoPath = null;

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
     * @ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\Tag", inversedBy="services")
     * @JoinTable(name="service_tag")
     * @Groups({"minimal", "normal", "extended"})
     **/
    private $tags;

    /**
     * @var array
     *
     * @ManyToMany(targetEntity="Hexaa\StorageBundle\Entity\SecurityDomain", inversedBy="services")
     * @JoinTable(name="service_security_domain")
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

    /**
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps() {
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload() {
        if (null !== $this->getLogo()) {
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->logoPath = $filename . '.' . $this->getLogo()->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload() {
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
            unlink($this->getUploadRootDir() . '/' . $this->tempFile);
            // clear the temp image path
            $this->tempFile = null;
        }
        $this->logo = null;
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload() {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

    /**
     * Get logo path
     *
     * @return string
     */
    public function getLogoPath() {
        if ($this->logoPath == null) {
            return null;
        } else {
            return $this->getUploadDir() . '/' . $this->logoPath;
        }
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Service
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Generate enableToken
     *
     * @return Service
     */
    public function generateEnableToken() {
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
     * Get enableToken
     *
     * @return string 
     */
    public function getEnableToken() {
        return $this->enableToken;
    }

    /**
     * Set minLoa
     *
     * @param integer $minLoa
     * @return Service
     */
    public function setMinLoa($minLoa) {
        $this->minLoa = $minLoa;

        return $this;
    }

    /**
     * Get minLoa
     *
     * @return integer 
     */
    public function getMinLoa() {
        return $this->minLoa;
    }

    /**
     * Set entityid
     *
     * @param string $entityid
     * @return Service
     */
    public function setEntityid($entityid) {
        $this->entityid = $entityid;

        return $this;
    }

    /**
     * Get entityid
     *
     * @return string 
     */
    public function getEntityid() {
        return $this->entityid;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Service
     */
    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Service
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Service
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Service
     */
    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Has manager
     *
     * @param Principal $manager
     *
     * @return boolean
     */
    public function hasManager(Principal $manager) {
        return $this->managers->contains($manager);
    }

    /**
     * Add managers
     *
     * @param Principal $managers
     * @return Service
     */
    public function addManager(Principal $managers) {
        $this->managers[] = $managers;

        return $this;
    }

    /**
     * Remove managers
     *
     * @param Principal $managers
     */
    public function removeManager(Principal $managers) {
        $this->managers->removeElement($managers);
    }

    /**
     * Get managers
     *
     * @return ArrayCollection
     */
    public function getManagers() {
        return $this->managers;
    }

    public function getAbsolutePath() {
        return null === $this->logoPath ? null : $this->getUploadRootDir() . '/' . $this->logoPath;
    }

    public function getWebPath() {
        return null === $this->logoPath ? null : $this->getUploadDir() . '/' . $this->logoPath;
    }

    protected function getUploadRootDir() {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    protected function getUploadDir() {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/service_logos';
    }

    /**
     * Sets logo.
     *
     * @param UploadedFile $file
     */
    public function setLogo(UploadedFile $file = null) {
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
     * Get logo.
     *
     * @return UploadedFile
     */
    public function getLogo() {
        return $this->logo;
    }

    /**
     * Add AttributeSpecs
     *
     * @param ServiceAttributeSpec $attributeSpecs
     * @return Service
     */
    public function addAttributeSpec(ServiceAttributeSpec $attributeSpecs) {
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
    public function removeAttributeSpec(ServiceAttributeSpec $attributeSpecs) {

        $attributeSpecs->setService(null);
        $this->attributeSpecs->removeElement($attributeSpecs);
    }

    /**
     * Get attributeSpecs
     *
     * @return ArrayCollection
     */
    public function getAttributeSpecs() {
        return $this->attributeSpecs;
    }

    /**
     * Has attributeSpec
     *
     * @param ServiceAttributeSpec $attributeSpec
     *
     * @return boolean
     */
    public function hasAttributeSpec(ServiceAttributeSpec $attributeSpec) {
        return $this->attributeSpecs->contains($attributeSpec);
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
     * Get orgName
     *
     * @return string 
     */
    public function getOrgName()
    {
        return $this->orgName;
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
     * Get orgShortName
     *
     * @return string 
     */
    public function getOrgShortName()
    {
        return $this->orgShortName;
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
     * Get orgUrl
     *
     * @return string 
     */
    public function getOrgUrl()
    {
        return $this->orgUrl;
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
     * Get isEnabled
     *
     * @return boolean 
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
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
     * Get orgDescription
     *
     * @return string 
     */
    public function getOrgDescription()
    {
        return $this->orgDescription;
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
        
        $this->setprivacyPolicySetAt(new \DateTime('now'));

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
     * Set privDescription
     *
     * @param string $privDescription
     * @return Service
     */
    public function setPrivDescription($privDescription)
    {
        $this->privDescription = $privDescription;
        
        $this->setprivacyPolicySetAt(new \DateTime('now'));

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
     * Get privacyPolicySetAt
     *
     * @return \DateTime
     */
    public function getPrivacyPolicySetAt()
    {
        return $this->privacyPolicySetAt;
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
     * Add tags
     *
     * @param Tag $tags
     * @return Service
     */
    public function addTag(Tag $tags)
    {
        $this->tags[] = $tags;

        return $this;
    }

    /**
     * Remove tags
     *
     * @param Tag $tags
     */
    public function removeTag(Tag $tags)
    {
        $this->tags->removeElement($tags);
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
    public function hasTag(Tag $tag){
        return $this->tags->contains($tag);
    }

    /**
     * Add securityDomains
     *
     * @param \Hexaa\StorageBundle\Entity\SecurityDomain $securityDomains
     * @return Service
     */
    public function addSecurityDomain(SecurityDomain $securityDomains)
    {
        $this->securityDomains[] = $securityDomains;

        return $this;
    }

    /**
     * Remove securityDomains
     *
     * @param \Hexaa\StorageBundle\Entity\SecurityDomain $securityDomains
     */
    public function removeSecurityDomain(SecurityDomain $securityDomains)
    {
        $this->securityDomains->removeElement($securityDomains);
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
    public function hasSecurityDomain(SecurityDomain $securityDomain){
        return $this->tags->contains($securityDomain);
    }

    public function __toString() {
        return $this->name;
    }
}
