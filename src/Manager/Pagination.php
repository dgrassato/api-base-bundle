<?php

namespace BaseBundle\Manager;

use Doctrine\ORM\QueryBuilder;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\Collection;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use League\Fractal;
use Pagerfanta\Adapter\MongoAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class Pagination
{
    /**
     * @var \Interop\Router\RouterInterface $router
     */
    private $router;

    /**
     * @param                                                $collection
     * @param                                                $routeName
     * @param \Symfony\Component\HttpFoundation\Request|NULL $request
     *
     * @return \League\Fractal\Pagination\PagerfantaPaginatorAdapter
     */
    public function getFractalPaginator(
        $collection,
        $routeName,
        Request $request = NULL
    ) {

        /**
         * @var AdapterInterface $adapter
         */
        $adapter = $this->getPaginateAdapter($collection);

        /**
         * @var Pagerfanta $pagerfanta
         */
        $pagerfanta = $this->getPaginator($adapter, $request);

        $gerador = function ($page) use ($routeName) {
            return $this->getRouter()->generate(
                $routeName,
                array('page' => $page),
                RouterInterface::ABSOLUTE_URL // ABSOLUTE_URL, ABSOLUTE_PATH, RELATIVE_PATH, NETWORK_PATH
            );
        };

        $resourcePaginatorAdapter = new PagerfantaPaginatorAdapter($pagerfanta, $gerador);

        return $resourcePaginatorAdapter;
    }


    /**
     * @param                                           $adapter
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Pagerfanta\Pagerfanta
     */
    private function getPaginator(AdapterInterface $adapter, Request $request)
    {

        $pagerfanta = new Pagerfanta($adapter);

        if ($request === NULL) {
            $request = Request::createFromGlobals();
        }

        if ($request->getMethod() === 'POST') {
            $inputData = $request->request->all();
        }
        else {
            $inputData = $request->query->all();
        }

        $page    = isset($inputData['page']) ? $inputData['page'] : 1;
        $perPage = isset($inputData['perPage']) ? $inputData['perPage'] : 10;

        // Fix, if perPage less than 10, set page 10
        if ($perPage < 10) {
            $perPage = 10;
        }

        // Fix, if page less than 0, set page 1
        if ($page < 1) {
            $page = 1;
        }


        // Fix, if page less than nbPages, set page value equal nbPages
        if ( $pagerfanta->getNbPages() < $page){
            $page = $pagerfanta->getNbPages();
        }

        $pagerfanta->setCurrentPage($page);
        $pagerfanta->setMaxPerPage($perPage);

        return $pagerfanta;
    }


    /**
     * @param $collection
     *
     * @return \RuntimeException|\Pagerfanta\Adapter\AdapterInterface
     */
    private function getPaginateAdapter($collection, \Pagerfanta\Adapter\AdapterInterface $adapter = null)
    {

        if ($adapter){
            return $adapter;
        }

        if (is_array($collection)) {


            return new ArrayAdapter($collection);
        }

        if (is_object($collection)) {

            $class = get_class($collection);

            switch($class) {

                // Doctrine ORM
                case \Doctrine\ORM\QueryBuilder::class:

                    $adapter = new \Pagerfanta\Adapter\DoctrineORMAdapter($collection);
                    break;

                case \Doctrine\Common\Collections\Collection::class:
                    $adapter = new \Pagerfanta\Adapter\DoctrineCollectionAdapter($collection);
                    break;

                // Doctrine MongoDB ODM
                case \MongoCursor::class:
                    $adapter = new \Pagerfanta\Adapter\MongoAdapter($collection);
                    break;

                case \Doctrine\ODM\MongoDB\Query\Builder::class:
                    $adapter = new \Pagerfanta\Adapter\DoctrineODMMongoDBAdapter($collection);
                    break;

                default;
                    throw new \RuntimeException('Please make a valid adapter...');
            }

        }

        if($adapter === null){

            throw new \RuntimeException("Adapter not found!!!");
        }

        return $adapter;
    }

    /**
     * @return \Interop\Router\RouterInterface
     */
    public function getRouter() {
        return $this->router;
    }

    /**
     * @param \Interop\Router\RouterInterface $router
     *
     * @return \Interop\Router\RouterInterface
     */
    public function setRouter($router) {
        $this->router = $router;
        return $this;
    }


}
