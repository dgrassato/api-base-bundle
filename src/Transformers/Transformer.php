<?php

namespace BaseBundle\Transformers;

use League\Fractal;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 *Transformer that includes the router service for generating routes.
 */
class Transformer extends Fractal\TransformerAbstract
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected $router;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager;
     */
    protected $manager;

    /**
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * @var array
     */
    protected $defaultIncludes = [];

    public function __construct()
    {
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string      $route         The name of the route
     * @param mixed       $parameters    An array of parameters
     * @param bool|string $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->getRouter()->generate($route, $parameters, $referenceType);
    }

    /**
     * Shortcut to $this->getManager()->getRepository();.
     *
     * @param $entity
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository($entity)
    {
        return $this->getManager()->getRepository($entity);
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Routing\Router $router
     *
     * @return Router
     */
    public function setRouter($router)
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     *
     * @return ObjectManager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        return $this;
    }


}
