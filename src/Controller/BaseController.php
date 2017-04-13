<?php

namespace BaseBundle\Controller;

use BaseBundle\Exception\ApiProblem;
use BaseBundle\Exception\ApiProblemException;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BaseController
 * @package BaseBundle\Controller
 */
abstract class BaseController extends FOSRestController
{
    /**
     * This method should return default manager.
     *
     * @abstract
     *
     * @return ObjectManager
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
     * @abstract
     * @return Transformer
     */
    abstract protected function getTransformer();

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
            $problem->setExtra(['filters' => $e]);
            throw new ApiProblemException($problem);

        }

        return $data;
    }

    /**
     * @param $content
     *
     * @return array|\JMS\Serializer\scalar|object
     */
    protected function unserializeClass($content)
    {
        try {

            $class       = $this->getEntityClass();
            $contentJson = $this->unserialize($content, $class, 'json');

            return $contentJson;

        } catch (\Exception $e) {

            $problem = new ApiProblem(400);
            $problem->setTitle("Invalid serializer class exception.");
            $problem->setExtra(['filters' => $e]);
            throw new ApiProblemException($problem);

        }
    }

    /**
     * Apply filters
     *
     * @param $objeto
     */
    public function fitler($objeto)
    {
        $filterService = $this->get('dms.filter');

        try {
            $filterService->filterEntity($objeto);
        } catch (\Exception $e) {
            $problem = new ApiProblem(400);
            $problem->setTitle("Invalid filter exception.");
            $problem->setExtra(['filters' => $e->getMessage()]);
            throw new ApiProblemException($problem);
        }
    }

    /**
     * Tranform array in object
     *
     * @param        $data
     * @param string $format
     *
     * @return mixed|string
     */
    protected function serialize($data, $groups = [], $format = 'json')
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
}
