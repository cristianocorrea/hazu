<?php

namespace CCS\HazuServiceBundle\Service;

use CCS\HazuServiceBundle\Response;
use CCS\HazuServiceBundle\Exception;
use CCS\HazuServiceBundle\Implementation\Entity as Entity;

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
