<?php

namespace BaseBundle\Entity\Traits;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class UiidTrait.
 */
trait UiidTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="UUID")
     * @Serializer\Groups({"identify"})
     */
    protected $uuid;

    /**
     * @return int
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param int $uuid
     *
     * @return UiidTrait
     */
    public function setUuid(int $uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function __clone()
    {
        $this->uuid = null;
    }
}
