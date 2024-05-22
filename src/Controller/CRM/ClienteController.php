<?php

namespace CrosierSource\CrosierLibRadxBundle\Controller\CRM;

use CrosierSource\CrosierLibBaseBundle\Utils\APIUtils\CrosierApiResponse;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;
use CrosierSource\CrosierLibRadxBundle\Repository\CRM\ClienteRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller interno do bundle com métodos/rotas que devem estar disponíveis em quaisquer apps.
 *
 * @author Carlos Eduardo Pauluk
 */
class ClienteController extends AbstractController
{

    private EntityManagerInterface $doctrine;

    public function __construct(
        ContainerInterface $container,
        Registry    $managerRegistry)
    {
        $this->container = $container;
        $this->doctrine = $managerRegistry->getManager();
    }

    public function findProxCodigo(): JsonResponse
    {
        /** @var ClienteRepository $repoCliente */
        $repoCliente = $this->doctrine->getRepository(Cliente::class);
        $proximo = $repoCliente->findProxCodCliente();
        return CrosierApiResponse::success($proximo);
    }


}
