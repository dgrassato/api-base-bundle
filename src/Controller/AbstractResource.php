<?php

namespace BaseBundle\Controller;

use BaseBundle\Exception\ApiProblem;
use BaseBundle\Exception\ApiProblemException;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AbstractResource
 * @package BaseBundle\Controller
 */
abstract class AbstractResource extends FOSRestController
{
    /**
     * This method should return default manager.
     *
     * @abstract
     *
     * @return getManager
     */
    abstract protected function getManager();

    /**
     * This method should return the entity.
     *
     * @abstract
     *
     * @return getEntiyClass
     */
    abstract protected function getEntityClass();

    /**
     * This method should return the default transform.
     * @format NameSpace + .transformer.+ Class, all in lower case
     * @see appbundle.transformer.user
     * @param $otherTransformer appbundle.transformer.user
     * @return Transformer
     */
     protected function getDefaultTransformer()
     {
         $class = new \ReflectionClass($this->getEntityClass());

         $namespace = substr($class->getNamespaceName(), 0, strrpos($class->getNamespaceName(), '\\'));

         $transformer = sprintf('%s.transformer.%s', strtolower($namespace), strtolower($class->getShortName()));

         if ($this->container->has($transformer)) {

             return $this->get($transformer);
         }


     }

    /**
     * @return \Symfony\Component\Validator\ValidatorInterface
     */
    protected function getValidator()
    {
        return $this->get('validator');
    }

    /**
     * @return \JMS\Serializer\SerializerInterface
     */
    public function getSerializer()
    {
        return $this->get('jms_serializer');
    }

    /**
     * Return repository.
     *
     * @param string $entity namespace
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository($entity)
    {
        return $this->getDoctrine()->getRepository($entity);
    }

    /**
     * @param string $classe
     *
     * @return array|\JMS\Serializer\scalar|object
     *
     * @throws Exception
     */
    public function unserialize(
        $content,
        $class,
        $type = 'json',
        $context = NULL
    ) {
        try {
            $data = $this->getSerializer()->deserialize(
                $content,
                $class,
                $type,
                $context
            );
        } catch (\Exception $e) {

            $problem = new ApiProblem(400);
            $problem->setTitle("Invalid serializer exception.");
            $problem->setExtra(['filters' =>
                                   ['message' => $e->getMessage()],
                                   ['code' => $e->getCode()],
                                   ['line' => $e->getLine()],
                                   ['string' => $e->getTraceAsString()],
                               ]);
            throw new ApiProblemException($problem);

        }

        return $data;
    }

    /**
     * @param $content
     *
     * @return array|\JMS\Serializer\scalar|object
     */
    protected function unserializeClass(\Symfony\Component\HttpFoundation\Request $request)
    {
        try {

            /**
             * @var \Symfony\Component\HttpFoundation\Request $request
             */
            //$request = $this->get('request_stack')->getCurrentRequest();
            $content = $request->getContent();
            $class       = $this->getEntityClass();

            $contentJson = $this->unserialize($content, $class, 'json');

            return $contentJson;

        } catch (\Exception $e) {

            $problem = new ApiProblem(400);
            $problem->setTitle("Invalid serializer class exception.");
            $problem->setExtra(['filters' =>
                                   ['message' => $e->getMessage()],
                                   ['code' => $e->getCode()],
                                   ['line' => $e->getLine()],
                                   ['string' => $e->getTraceAsString()],
                               ]);
            throw new ApiProblemException($problem);

        }
    }

    /**
     * Apply filters
     *
     * @param $objeto
     */
    public function filter($objeto)
    {
        $filterService = $this->get('dms.filter');

        try {
            $filterService->filterEntity($objeto);
        } catch (\Exception $e) {
            $problem = new ApiProblem(400);
            $problem->setTitle("Invalid filter exception.");
            $problem->setExtra(['filters' =>
                                   ['message' => $e->getMessage()],
                                   ['code' => $e->getCode()],
                                   ['line' => $e->getLine()],
                                   ['string' => $e->getTraceAsString()],
                               ]);
            throw new ApiProblemException($problem);
        }
    }

    /**
     * Tranform array in object(class)
     *
     * @param        $data
     * @param string $format
     *
     * @return mixed|string
     */
    protected function serialize($data, $groups = [], $version = null, $format = 'json')
    {
        $context = new SerializationContext();
        $context->setSerializeNull(TRUE);

        $request = $this->get('request_stack')->getCurrentRequest();

        if (count($groups) < 1) {
            $groups = array('Default');
        }
        if ($request->query->get('deep')) {
            $groups[] = 'deep';
        }
        $context->setGroups($groups);

        if($version) {
            $context->setVersion($version);
        }

        return $this->container->get('jms_serializer')
            ->serialize($data, $format, $context);
    }

    /**
     * Validate data
     *
     * @param      $value
     * @param null $groups
     * @param bool $traverse
     * @param bool $deep
     *
     * @return mixed
     */
    public function validate(
        $value,
        $constraints = NULL,
        $groups = NULL,
        $traverse = FALSE,
        $deep = FALSE
    ) {
        $erros = $this->getValidator()
            ->validate($value, $constraints, $groups, $traverse, $deep);

        if (count($erros) > 0) {
            $details = [];
            $problem = new ApiProblem(400);
            $problem->setTitle("Invalid argument exception.");
            foreach ($erros as $e) {
                $details[] = array(
                    'field'       => $e->getPropertyPath(),
                    'description' => $e->getMessage()
                );
            }


            $problem->setExtra(['validations' => $details]);
            throw new ApiProblemException($problem);
        }

        return $value;
    }

    protected function createApiResponse(
        $data,
        $serializerGroups = [],
        $statusCode = 200
    ) {
        $json = $this->serialize($data, $serializerGroups);

        return new Response($json, $statusCode, array(
            'Content-Type' => 'application/json'
        ));
    }

    /**
     * Create a resource
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function create($data)
    {
        return new ApiProblem(405, 'The POST method has not been defined');
    }

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function delete($id)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for individual resources');
    }

    /**
     * Delete a collection, or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function deleteList($data)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for collections');
    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {
        return new ApiProblem(405, 'The GET method has not been defined for individual resources');
    }

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return ApiProblem|mixed
     */
    public function fetchAll($params = [])
    {
        return new ApiProblem(405, 'The GET method has not been defined for collections');
    }

    /**
     * Patch (partial in-place update) a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function patch($id, $data)
    {
        return new ApiProblem(405, 'The PATCH method has not been defined for individual resources');
    }

    /**
     * Patch (partial in-place update) a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function patchList($data)
    {
        return new ApiProblem(405, 'The PATCH method has not been defined for collections');
    }

    /**
     * Replace a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function replaceList($data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for collections');
    }

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function update($id, $data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for individual resources');
    }
}
