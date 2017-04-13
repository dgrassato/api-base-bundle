<?php

namespace BaseBundle\Transformers;

use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Serializer\ArraySerializer;

class HipermediaSerializer extends ArraySerializer
{

    /**
     * Serialize a collection.
     *
     * @param string $resourceKey
     * @param array  $data
     *
     * @return array
     */
    public function collection($resourceKey, array $data)
    {

        $resource = [
            $resourceKey => $data,
        ];

        return $resource;

    }


    /**
     * Serialize an item.
     *
     * @param string $resourceKey
     * @param array  $data
     *
     * @return array
     */
    public function item($resourceKey, array $data)
    {
        $resource = [
            $resourceKey => $data,
        ];


        return $resource;
    }


    /**
     * Serialize the included data.
     *
     * @param ResourceInterface $resource
     * @param array             $data
     *
     * @return array
     */
    public function includedData(ResourceInterface $resource, array $data)
    {
        list($serializedData, $linkedIds) = $this->pullOutNestedIncludedData(
            $resource,
            $data
        );

        foreach ($data as $value) {
            foreach ($value as $includeKey => $includeObject) {

                if ($this->isNull($includeObject) || $this->isEmpty($includeObject)) {
                    continue;
                }

                if ($this->isCollection($includeObject)) {

                    $includeCollectionObjects = $includeObject['data'];

                    foreach ($includeCollectionObjects as $collection) {

                        $includeObjects[$collection['type']][] = $collection['attributes'];
                    }

                }
                else {

                    $key                     = array_keys($includeObject);
                    $value                   = array_values($includeObject);
                    $includeObjects[$key[0]] = $value[0];

                }
                $serializedData = ['_embedded' => $includeObjects];

            }
        }

        return empty($serializedData) ? [] : $serializedData;//['_embedded' => $serializedData];
    }

    /**
     * Keep all sideloaded inclusion data on the top level.
     *
     * @param ResourceInterface $resource
     * @param array             $data
     *
     * @return array
     */
    protected function pullOutNestedIncludedData(
        ResourceInterface $resource,
        array $data
    ) {
        $includedData = [];
        $linkedIds    = [];

        foreach ($data as $value) {
            foreach ($value as $includeKey => $includeObject) {
                if (isset($includeObject['included'])) {
                    foreach ($includeObject['included'] as $object) {
                        $includeType = $object['type'];
                        $includeId   = $object['id'];
                        $cacheKey    = "$includeType:$includeId";

                        if (!array_key_exists($cacheKey, $linkedIds)) {
                            $includedData[]       = $object;
                            $linkedIds[$cacheKey] = $object;
                        }
                    }
                }
            }
        }

        return [$includedData, $linkedIds];
    }

    protected function isNull($data)
    {
        return array_key_exists('data', $data) && $data['data'] === NULL;
    }

    protected function isEmpty($data)
    {
        return array_key_exists('data', $data) && $data['data'] === [];
    }

    protected function isCollection($data)
    {
        return array_key_exists('data', $data) &&
            array_key_exists(0, $data['data']);
    }

    /**
     * Indicates if includes should be side-loaded.
     *
     * @return bool
     */
    public function sideloadIncludes()
    {
        return TRUE;
    }

}
