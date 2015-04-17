<?php

namespace CCS\HazuBundle\Service;

use CCS\HazuBundle\Response;
use CCS\HazuBundle\Exception;
use CCS\HazuBundle\Implementation\Entity as Entity;

class HazuService
{
    protected $container;
    protected $serializer;
    protected $entityManager;

    public function __construct($container)
    {
        $this->container = $container;
        $this->serializer = $this->container->get('Hazu.Serializer');
        $this->entityManager = $this->container->get('doctrine')->getManager();
    }

    public function getEntity($entity)
    {
        return new Entity($this->container, $entity);
    }

    public function getSerializer()
    {
        return $this->serializer;
    }

    public function getEntityManager()
    {
        return $this->entityManager;
    }

    public function getContainer()
    {
        return $this->container;
    }
}
