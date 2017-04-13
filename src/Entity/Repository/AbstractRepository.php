<?php

namespace BaseBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Request;

class AbstractRepository extends EntityRepository
{

    /**
     * Create cached queryBuilder
     *
     * @param string $cache      The cache key.
     * @param string $queryAlias The cache alias, uses to set queryBuilder alias.
     *
     * @return \Doctrine\ORM\QueryBuilder.
     */
    public function getCacheQueryBuilder($cache = 'cache', $queryAlias = 'q')
    {
        $cacheId = md5($cache);
        $qb      = $this->createQueryBuilder($queryAlias);
        $qb->getQuery()
            ->useQueryCache(TRUE)
            ->useResultCache(TRUE, 3600, $cacheId);

        return $qb;
    }

    /**
     * Finds all objects with cache in the repository.
     *
     * @return array The objects.
     */
    public function fetchAll()
    {
        try {

            $key = sprintf(
                "paginate_%s_fetch_all",
                strtolower(str_replace("\\", "_slash_", $this->getClassName()))
            );

            $qb     = $this->getCacheQueryBuilder($key, 'u');
            $result = $qb->getQuery()
                         ->getResult();

            return $result;
        } catch (NoResultException $e) {
            return $e;
        }
    }

    /**
     * Finds a single and cached object by a set of id.
     *
     * @param integer $id The object.
     *
     * @return object|null The object.
     */
    public function fetchOneById($id)
    {
        try {

            $cache = sprintf(
                "paginate_%s_find_one_by_id",
                strtolower(str_replace("\\", "_slash_", $this->getClassName()))
            );

            $qb = $this->getCacheQueryBuilder($cache, 'u');
            $qb->where('u.id = ?1')
                ->setParameter(1, $id);

            $result = $qb->getQuery()
                ->getOneOrNullResult();

            return $result;
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * Generate cache pagination
     *
     * @param \Symfony\Component\HttpFoundation\Request|NULL $parameters
     * @param bool                                           $execute
     *
     * @return array|object|\Doctrine\ORM\QueryBuilder
     */
    public function paginate(Request $parameters = NULL, $execute = TRUE)
    {
        $cache = sprintf(
            "paginate_%s_pagination",
            strtolower(str_replace("\\", "_slash_", $this->getClassName()))
        );

        $qb = $this->getCacheQueryBuilder($cache, 'e');

        if ($parameters->getMethod() === 'POST') {
            $inputData = $parameters->request->all();
        }
        else {
            $inputData = $parameters->query->all();
        }

        $perPage = isset($inputData['perPage']) ? $inputData['perPage'] : 10;

        $qb->setMaxResults($perPage);

        if (array_key_exists('sort', $inputData) and !empty($inputData['sort'])) {

            $arraySort = $this->sanitizeDirectionFields($inputData['sort']);

            foreach ($arraySort as $sortConfig) {


                if ($this->getClassMetadata()->hasField($sortConfig['field'])) {

                    $qb->addOrderBy("e.${sortConfig['field']}", $sortConfig['direction']);
                }

            }
        }

        return $execute === TRUE ? $qb->getQuery()
            ->getResult()
            : $qb;
    }

    /**
     * Método responsável por parsear o field de ordenação de string para array.
     *
     * @param string $sort no formato: '-titulo,+criadoEm' para ordenar por: 'ORDER BY titulo DESC, criadoEm ASC'.
     *
     * @return array com os filtros no formato:
     * <code>
     * array(
     *  array('field' => 'titulo', 'direction' => 'DESC'),
     *  array('field' => 'criadoEm', 'direction' => 'ASC'),
     * )
     * </code>
     */
    protected function sanitizeDirectionFields($sort = '') {
        $ret    = [];
        $fields = explode(',', $sort);


        foreach ($fields as $field) {
            if (empty($field)) {
                continue;
            }

            $sort = [];

            if (0 === strpos($field, '-')) {
                $sort['field']     = trim(substr($field, 1));
                $sort['direction'] = 'DESC';
            }
            elseif (0 === strpos($field, '+')) {
                $sort['field']     = trim(substr($field, 1));
                $sort['direction'] = 'ASC';
            }
            else {
                $sort['field']     = trim($field);
                $sort['direction'] = 'ASC';
            }

            if (!empty($sort)) {
                $ret[] = $sort;
            }
        }

        return $ret;
    }
}
