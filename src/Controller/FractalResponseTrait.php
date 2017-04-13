<?php

namespace BaseBundle\Controller;

use BaseBundle\Exception\ApiProblem;
use BaseBundle\Exception\ApiProblemException;
use BaseBundle\Transformers\HipermediaSerializer;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use Psr\Log\LogLevel;

/**
 * Class FractalResponseTrait
 * @package BaseBundle\Controller
 */
trait FractalResponseTrait
{

    /**
     * Return collection response from the application
     *
     * @param        PaginatorInterface | array $collection
     * @param        \Closure|\League\Fractal\TransformerAbstract  $callback
     * @param array  $parsearIncludes
     * @param string $resourceKey data
     * @param array  $meta [key => value]
     *
     * @return array
     */
    protected function response(
        $collection,
        $callback,
        $parsearIncludes = [],
        $resourceKey = 'data',
        array $meta = []

    ) {

        if ($collection === null){
            return ApiProblemException::throw("No data available", ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT, LogLevel::WARNING);
        }

        if (is_a($collection, \Doctrine\ORM\QueryBuilder::class) && ! is_a($collection, PaginatorInterface::class)) {

            $collection = $collection->getQuery()
                ->getResult();

        }

        if(is_array($collection) || is_a($collection, PaginatorInterface::class)){

            return $this->respondWithCollection($collection,
                                   $callback,
                                   $parsearIncludes,
                                   $resourceKey,
                                   $meta);

        }

        return $this->respondWithItem($collection,
                               $callback,
                               $parsearIncludes,
                               $resourceKey,
                               $meta);

    }
    /**
     * Return collection response from the application
     *
     * @param        PaginatorInterface | array $collection
     * @param        \Closure|\League\Fractal\TransformerAbstract  $callback
     * @param array  $parsearIncludes
     * @param string $resourceKey data
     * @param array  $meta [key => value]
     *
     * @return array
     */
    protected function respondWithCollection(
        $collection,
        $callback,
        $parsearIncludes = [],
        $resourceKey = 'data',
        array $meta = []

    ) {

        $resource = new Collection($collection, $callback, $resourceKey);

        if (is_a($resource->getData(), PaginatorInterface::class)){

            $resource->setPaginator($resource->getData());
            $resource->setData($resource->getData()->getPaginator()->getIterator());
        }

        /**
         * @var \Doctrine\ORM\QueryBuilder $collection
         */
        if (is_a($collection, \Doctrine\ORM\QueryBuilder::class)) {

            $collectionQb = $collection->getQuery()
                ->getResult();

            $resource->setData($collectionQb);
        }

        $fractal = $this->getFractal();

        if (!empty($parsearIncludes)) {

            $fractal->parseIncludes($parsearIncludes);
        }

        if (!empty($meta)) {
            $resource->setMeta($meta);
        }

        $fractal->setSerializer(new HipermediaSerializer());
        $rootScope = $fractal->createData($resource);

        return $rootScope->toArray();
    }


    /**
     * Return single item response from the application
     *
     * @param        $item
     * @param        \Closure|\League\Fractal\TransformerAbstract $callback
     * @param array  $parsearIncludes
     * @param string $resourceKey data
     * @param array  $meta [key => value]
     *
     * @return array
     */
    public function respondWithItem(
        $item,
        $callback,
        $parsearIncludes = [],
        $resourceKey = 'data',
        array $meta = []
    ){

        $resource = new Item($item, $callback, $resourceKey);

        $fractal = $this->getFractal();

        if (!empty($parsearIncludes)) {
            $fractal->parseIncludes($parsearIncludes);
        }

        if (!empty($meta)) {
            $resource->setMeta($meta);
        }

        $fractal->setSerializer(new HipermediaSerializer());
        $rootScope = $fractal->createData($resource);

        return $rootScope->toArray();
    }

    /**
     * Get fractal Fractal\Manager instance
     *
     * @return Fractal\Manager
     */
    public function getFractal()
    {
        return new Fractal\Manager();
    }
}
