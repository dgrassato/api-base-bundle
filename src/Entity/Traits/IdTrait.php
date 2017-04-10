<?php

namespace BaseBundle\Entity\Traits;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class IdTrait.
 */
trait IdTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Serializer\Groups({"identify"})
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return IdTrait
     */
    public function setId($id)
    {
        if ((int) $id <= 0) {
            throw new \RuntimeException(__FUNCTION__.' accept only positive integers greater than zero and');
        }
        $this->id = $id;

        return $this;
    }

    public function __clone()
    {
        $this->id = null;
    }
}
