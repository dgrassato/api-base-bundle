<?php

namespace BaseBundle\Entity\Traits;

use Zend\Hydrator\Reflection;

/**
 * Class ArrayTrait.
 */
trait ArrayTrait
{
    /**
     * {@inheritdoc}
     */
    public function exchangeArray(array $array)
    {
        return (new Reflection())->hydrate($array, $this);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return (new Reflection())->extract($this);
    }
}
