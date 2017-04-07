<?php

namespace BaseBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class SoftDeleteTrait
 * @package AppBundle\Entity\EntityTrait
 */
trait SoftDeleteTrait
{

    /**
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     *
     * @Serializer\Exclude()
     */
    protected $deletedAt;

    /**
     * @return mixed
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param mixed $deletedAt
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

}
