<?php

namespace BaseBundle\Manager;

use BaseBundle\Exception\ApiProblemException;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Abstract Manager
 * @package BaseBundle\Manager
 */
abstract class AbstractManager
{
    /**
     * Instance of ObjectManager(em).
     *
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $manager;

    /**
     * Instance of EntityRepository
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repository;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Entity Class
     *
     * @var Entity
     */
    protected $entityClass;

    /**
     * Logger interface
     *
     * @var \Monolog\Logger
     */
    protected $logger;

    public function __construct(ObjectManager $manager, $entityClass = NULL)
    {
        $this->setManager($manager);

        if ($entityClass !== NULL) {
            $this->setEntityClass($entityClass);
            $this->setRepository($manager->getRepository($entityClass));
        }
    }


    public function transformArrayToObject(array $data)
    {
        $entityClass = $this->getEntityClass();
        $object      = new $entityClass();
        $object->exchangeArray($data);

        return $object;
    }

    /**
     * @param       $data
     * @param       $id
     * @param array $excludes
     */
    public function mergeObject($data, $id, $excludes = [])
    {

        $dafaultExcludes = ['id', 'createdAt', 'updatedAt', 'version'];
        $dafaultExcludes = array_merge($dafaultExcludes, $excludes);

        $accessor = PropertyAccess::createPropertyAccessor();

        $entity = $this->fetch($id);

        if ($entity === NULL) {

            return ApiProblemException::throw("Object " . $this->getEntityClass() . " is not exists,", 400);
        }

        foreach ($data as $key => $value) {

            if (!in_array($key, $dafaultExcludes)) {


                if ($value !== NULL) {

                    $accessor->setValue($entity, $key, $value);

                }

            }
        }

        return $entity;
    }

    /**
     * @param $id
     *
     * @return \Exception|null|object
     */
    public function fetch($id)
    {
        try {
            $ret = $this->getRepository($this->getEntityClass())
                ->fetchOneById(['id' => (int) $id]);
        } catch (\Exception $e) {
            return $e;
        }

        return $ret;
    }


    public function merge($object)
    {
        $this->getManager()->merge($object);

        $this->getManager()->flush();

        return $object;
    }


    public function save($data)
    {
        $this->getManager()->persist($data);
        $this->getManager()->flush();

        return $data;
    }

    public function delete($data, $flush = TRUE)
    {
        $entity = $data;

        if (is_numeric($entity)) {

            $entity = $this->fetch($data);
        }

        if ($entity instanceof \Doctrine\ORM\Proxy\Proxy) {
            return FALSE;
        }

        $this->getManager()->remove($entity);

        if ($flush) {
            $this->getManager()->flush();
        }

        return TRUE;
    }

    /**
     * Check class has sub entity and popule with real object
     *
     * @param $object
     *
     * @return mixed
     *
     */
    public function getAssociationTargetObject($object)
    {
        $entityClassMetadata = $this->getManager()
            ->getClassMetadata(get_class($object));

        $fieldMapping = $entityClassMetadata->getAssociationNames();

        foreach ($fieldMapping as $field) {

            $associationMapping = $this->getManager()
                ->getClassMetadata(get_class($object))
                ->getAssociationMappings();

            $associationTypeBoolean = $this->getManager()
                ->getClassMetadata(get_class($object))
                ->isCollectionValuedAssociation($field);

            if ($associationTypeBoolean && $associationMapping[$field]['type'] === 8) {

                $this->getAssociationMultipleObjects($object, $field);

            }
            else {

                $this->getAssociationSingleObject($object, $field);

            }
        }

        return $object;
    }

    /**
     * Populate multiple association objects
     *
     * @param $object
     *
     * @return mixed
     *
     */
    private function getAssociationMultipleObjects(&$object, $field)
    {

        $setMethod = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        $getMethod = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        $addMethod = 'add' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));

        $entityClass = get_class($object);
        $subClass    = $this->getManager()
            ->getClassMetadata($entityClass)
            ->getAssociationTargetClass($field);

        $identify = $this->getManager()
            ->getClassMetadata($subClass)
            ->getIdentifierFieldNames();


        if (!method_exists($object, $addMethod)) {

            return ApiProblemException::throw("Method ($entityClass::$addMethod) is not exists.", 400);
        }

        if (!method_exists($object, $getMethod)) {

            return ApiProblemException::throw("Method ($entityClass::$getMethod) is not exists.", 400);
        }

        $idIdentify = [];
        $iterate    = $object->$getMethod();

        if (count($iterate) > 0) {

            foreach ($iterate as $elements) {

                if (is_object($elements)) {

                    $elements = $elements->getId();
                }

                $idIdentify[] = $elements;
            }

            $object->$setMethod(NULL);

            $entityClassCollectionValue = $this->getManager()
                ->getRepository($subClass)
                ->findBy([$identify[0] => $idIdentify], [$identify[0] => 'DESC']);

            $object->$addMethod($entityClassCollectionValue);

        }

    }

    /**
     * Populate single association objects
     *
     * @param $object
     *
     * @return mixed
     *
     */
    private function getAssociationSingleObject(&$object, $field)
    {
        $setMethod = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        $getMethod = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));

        $entityClass = get_class($object);
        $subClass    = $this->getManager()
            ->getClassMetadata($entityClass)
            ->getAssociationTargetClass($field);

        $identify = $this->getManager()
            ->getClassMetadata($subClass)
            ->getIdentifierFieldNames();

        if (!method_exists($object, $setMethod)) {

            return ApiProblemException::throw("Method ($entityClass::$setMethod) is not exists.", 417);
        }

        if (!method_exists($object, $getMethod)) {

            return ApiProblemException::throw("Method ($entityClass::$getMethod) is not exists.", 417);
        }

        $element = $object->$getMethod();

        if ($element === null ){
           return;
        }

        if (is_object($element)) {

            $element = $element->getId();
        }

        $object->$setMethod(NULL);

        $entityClassValue = $this->getManager()
            ->getRepository($subClass)
            ->findOneBy([$identify[0] => $element]);

        if ($entityClassValue === NULL) {

            return ApiProblemException::throw("Object $subClass($element) is not exists.", 422);
        }

        $object->$setMethod($entityClassValue);

    }

    public function dispatchEvent($eventName, Event $event)
    {
        $this->getEventDispatcher()->dispatch($eventName, $event);
    }

    /**
     * Retrieve the event manager instance.
     *
     * Lazy-initializes one if none present.
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        if (!$this->eventDispatcher) {
            $this->setEventDispatcher(new EventDispatcher());
        }

        return $this->eventDispatcher;
    }

    /**
     * Set the event manager instance.
     *
     * @param EventManagerInterface $events
     *
     * @return self
     */
    public function setEventDispatcher($dispatcher)
    {
        $this->eventDispatcher = $dispatcher;

        return $this;
    }

    /**
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param \Monolog\Logger $logger
     *
     * @return AbstractManager
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param \Doctrine\ORM\EntityRepository $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @return ObjectManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param ObjectManager $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        return $this;
    }
}
