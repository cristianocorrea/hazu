<?php

namespace CCS\HazuServiceBundle\Service;

use JMS\Serializer;

class SerializerService
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function unserialize($object, $jsonData)
    {
        $jsonData = is_array($jsonData) ? json_encode($jsonData) : $jsonData;
        $serializer = SerializerBuilder::create()->build();
        $jmsObject = $serializer->deserialize($jsonData, $object, 'json');
        $jmsMethods = get_class_methods($jmsObject);
        $id = $jmsObject->getId();

        if ($id) {
            $ormObject = $this->findFromOrm($object, $id);
            $ormMethods = get_class_methods($ormObject);

            foreach ($jmsMethods as $i => $function) {//Intera todos os methodos do objeto
                $setFunc = 'set' . substr($function, 3); //Setter Function

                if (substr($function, 0, 3) === 'get' && //Caso seja uma função getter
                    $jmsObject->$function() !== null  && //tenha um valor setado
                    in_array($setFunc, $ormMethods)) { // exista a função setter no orm

                    if (!is_object($jmsObject->{$function}())) { //atributo simples
                        $ormObject->$setFunc($jmsObject->{$function}());
                    }
                }
            }
        } else {

            /*
             * Caso objeto seja novo e não tenha id, confere se existe alguma entidade relacionada enviada junto
             * caso exista carrega a entidade via doctrine para nao gera problemas no persist
             */

            foreach ($jmsMethods as $i => $function) {//Intera todos os methodos do objeto

                //Caso seja uma função getter && tenha um valor setado && exista a função setter no orm
                if (substr($function, 0, 3) === 'get') {

                    $setFunc = 'set' . substr($function, 3); //Setter Function
                    $prop = $jmsObject->{$function}();

                    //Reload de objeto simples
                    if (is_object($prop) && method_exists($prop, 'getId') && $prop->getId()) {
                        $jmsObject->{$setFunc}( $this->findFromOrm(get_class($prop), $prop->getId()));
                    } elseif (is_object($prop) && get_class($prop) == 'Doctrine\Common\Collections\ArrayCollection') {
                        $elements = $prop->getValues();

                        foreach ($elements as $i => $element) {
                            $prop->offsetSet($i, $this->findFromOrm(get_class($element), $element->getId()));
                        }

                        $jmsObject->{$setFunc}($prop);
                    }
                }
            }
        }

        return $id ? $ormObject : $jmsObject;
    }

    public function findFromOrm($object, $id)
    {
        $repository = $this->container->get('doctrine')->getManager();
        return  $repository->createQueryBuilder()
                ->select('o')
                ->from($object, 'o')
                ->where('o.id = :id')
                ->setParameter('id', $id)
                ->getQuery()->getSingleResult();
    }
}
