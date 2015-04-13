<?php

namespace CCS\HazuServiceBundle\Implementation;

/**
 * Class EntityService
 * @package CCS\HazuServiceBundle\Implementation
 */
class Entity
{
    protected $container;
    protected $serializer;
    protected $entityManager;
    protected $entity;

    public function __construct($container, $entity)
    {
        $this->container = $container;
        $this->serializer = $this->container->get('Hazu.Serializer');
        $this->entityManager = $this->container->get('doctrine')->getManager();
        $this->entity = $entity;
    }

    public function read($id)
    {
        $id = is_numeric($id) ? $id : null;

        return  $this->entityManager->createQueryBuilder()
                ->select('o')
                ->from($this->entity, 'o')
                ->where('o.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getOneOrNullResult();
    }

    public function save($params)
    {
        unset($params['id']); //Id is auto-generator
        $exercise = $this->serializer->unserialize($this->entity, $params);
        $this->entityManager->persist($exercise);
        $this->entityManager->flush();
        return $exercise;
    }

    public function patch($params)
    {
        $exercise = $this->serializer->unserialize($this->entity, $params);
        $this->entityManager->persist($exercise);
        $this->entityManager->flush();
        return $exercise;
    }

    public function delete($id)
    {
        return (bool)$this->entityManager->createQueryBuilder()
                ->delete($this->entity, 'o')
                ->where('o.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->execute();
    }

    public function listAll()
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from($this->entity, 'o')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $params
     * @param array $AQF Campos da entidade permitidos para busca
     * @param array $AQO Operadores permitidos ('=','like','<=','>=','<>')
     * @throws \Exception
     */
    public function find($params, $AQF = array(), $AQO = array())
    {
        //Validação
        if (!isset($params['query']) || !is_array($params['query'])) {
            throw new  \Exception('Field query is required');
        }
        $queryType = (isset($params['queryType']) && 'OR' === strtoupper($params['queryType'])) ? ' OR ' : ' AND ';

        $qb = $this->entityManager->createQueryBuilder();

        $select = 'o';
        if (isset($params['fields']) && is_array($params['fields'])) {
            $fields = array();

            foreach ($params['fields'] as $field) {
                $fields[] = 'o.'.$field;
            }

            $select = implode(', ', $fields);
        }
        $qb ->select($select)->from($this->entity, 'o');

        //Construindo o criterio where atraves dos parametros query e queryType
        $where = array();
        $whereParameters = array();
        $count = 0;

        $regex = '/^([a-z]+)\s+((\=|\>\=|\<\=|like|\<\>|in)\s)+(.+)/i';

        foreach ($params['query'] as $i => $value) {
            if (preg_match($regex, $value, $matches)) {
                $operator = $matches[3];
                $field = $matches[1];
                $value = $matches[4];

                if (in_array($field, $AQF)) {
                    if (in_array($operator, $AQO)) {
                        $where[$count] = "o.{$field} {$operator} ?{$count}";
                        $whereParameters[$count] =  $value;
                        ++$count;
                    } else {
                        throw new  \Exception("Operator: {$operator} not allowed. Use \$AQO to allow");
                    }
                } else {
                    throw new  \Exception(" Field: {$field} not allowed. Use \$AQF to allow");
                }
            }
        }

        $qb ->where(implode($queryType, $where));
        $qb ->setParameters($whereParameters);
        //============================================================//

        return $qb ->getQuery()->getResult();
    }
}
