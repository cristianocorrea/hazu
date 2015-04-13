<?php

namespace CCS\HazuServiceBundle\Controller;

use CCS\HazuServiceBundle\Response as HazuServiceResponse;
use CCS\HazuServiceBundle\Exception\Exception as HazuServiceException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;

/*
 * Annotations
 */
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class KernelController
 * @package CCS\HazuServiceBundle\Controller
 */
class KernelController extends Controller
{

    /**
     *  RPC url action
     *
     * @param $bundle
     * @param $service
     * @param $method
     * @param Request $request
     *
     * @Route("/{bundle}/{service}/{method}" , defaults={"_format": "json"})
     * @Method("POST")
     *
     * @return Response
     */
    public function rpcAction($bundle, $service, $method, Request $request)
    {
        $response = new Response();
        $translator = $this->get('translator');

        try {
            $prefix = 'Hazu.Service';
            $serviceObject = $this->get("{$prefix}.{$bundle}.{$service}");

            if (true === method_exists($serviceObject, $method)) {
                $params = json_decode($request->getContent(), true);

                if (null === $params) {
                    throw new \Exception('$params não é um JSON valido');
                }
                
                $rService = $serviceObject->{$method}($params);

            } else {
                throw new \Exception($translator->trans('Metodo não encontrado'));
            }

        } catch (ServiceNotFoundException $e) {
            $rService = new HazuServiceException($e->getMessage());
            $response->setStatusCode(500);

        } catch (\Exception $e) {
            $rService = new HazuServiceException($e->getMessage());
            $response->setStatusCode(500);
        } finally {
            $serializer = SerializerBuilder::create()->build();
            $rJson = $serializer->serialize(
                $rService,
                'json',
                SerializationContext::create()->enableMaxDepthChecks()
            );

            $response->headers->set('x-hazu-type', gettype($rService));

            if (gettype($rService) == 'object') {
                $response->headers->set('x-hazu-class', get_class($rService));
            }

            $response->setContent($rJson);
        }

        return $response;
    }
}
