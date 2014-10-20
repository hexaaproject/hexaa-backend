<?php

namespace Hexaa\StorageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Hexaa\ApiBundle\Validator\Constraints as HexaaAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Service
 *
 * @ORM\Table(name="service", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})})
 * @ORM\Entity(repositoryClass="Hexaa\StorageBundle\Entity\ServiceRepository")
 * @UniqueEntity("name")
 * @ORM\HasLifecycleCallbacks
 */
class Service {

    /**
     * @ORM\ManyToMany(targetEntity="Principal")
     * @Exclude
     */
    private $managers;
    private $tempFile;

    public function __construct() {
        $this->managers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attributeSpecs = new \Doctrine\Common\Collections\ArrayCollection();
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
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="ServiceAttributeSpec", mappedBy="service", cascade={"persist"}, orphanRemoval=true)
     * @Assert\Valid(traverse=true)
     * @Exclude
     */
    private $attributeSpecs;

    /**
     * @var string
     *
     * @ORM\Column(name="entityid", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @HexaaAssert\ValidEntityid()
     */
    private $entityid;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="org_name", type="string", length=255, nullable=true)
     */
    private $orgName;

    /**
     * @var string
     *
     * @ORM\Column(name="org_short_name", type="string", length=255, nullable=true)
     */
    private $orgShortName;

    /**
     * @var string
     *
     * @ORM\Column(name="org_url", type="string", length=255, nullable=true)
     */
    private $orgUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="org_description", type="text", nullable=true)
     */
    private $orgDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="priv_url", type="string", length=255, nullable=true)
     */
    private $privUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="priv_description", type="text", nullable=true)
     */
    private $privDescription;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=true)
     */
    private $isEnabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_loa", type="bigint", nullable=true)
     */
    private $minLoa = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Exclude
     */
    public $logoPath = null;

    /**
     * @Assert\File(maxSize="6000000")
     * @Exclude
     */
    private $logo = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="privacy_policy_set_at", type="datetime", nullable=true)
     */
    private $privacyPolicySetAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
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
     * @VirtualProperty
     * @SerializedName("logo_path")
     * @Type("string")
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
     * @param \Hexaa\StorageBundle\Entity\Principal $manager
     *
     * @return boolean
     */
    public function hasManager(\Hexaa\StorageBundle\Entity\Principal $manager) {
        return $this->managers->contains($manager);
    }

    /**
     * Add managers
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $managers
     * @return Service
     */
    public function addManager(\Hexaa\StorageBundle\Entity\Principal $managers) {
        $this->managers[] = $managers;

        return $this;
    }

    /**
     * Remove managers
     *
     * @param \Hexaa\StorageBundle\Entity\Principal $managers
     */
    public function removeManager(\Hexaa\StorageBundle\Entity\Principal $managers) {
        $this->managers->removeElement($managers);
    }

    /**
     * Get managers
     *
     * @return \Doctrine\Common\Collections\Collection 
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
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecs
     * @return Service
     */
    public function addAttributeSpec(\Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecs) {
        $this->attributeSpecs[] = $attributeSpecs;

        if ($attributeSpecs->getService() !== $this) {
            $attributeSpecs->setService($this);
        }

        return $this;
    }

    /**
     * Remove AttributeSpecs
     *
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecs
     */
    public function removeAttributeSpec(\Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpecs) {

        $attributeSpecs->setService(null);
        $this->attributeSpecs->removeElement($attributeSpecs);
    }

    /**
     * Get attributeSpecs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAttributeSpecs() {
        return $this->attributeSpecs;
    }

    /**
     * Has attributeSpec
     *
     * @param \Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpec
     *
     * @return boolean
     */
    public function hasAttributeSpec(\Hexaa\StorageBundle\Entity\ServiceAttributeSpec $attributeSpec) {
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
        
        /*
        $this->logoPath = $logoPath;

        return $this;
         */
    }
}
