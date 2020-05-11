<?php

namespace CrosierSource\CrosierLibBaseBundle\Business\Vendas;

use CrosierSource\CrosierLibBaseBundle\APIClient\CrosierEntityIdAPIClient;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaEntityHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class VendaBusiness
 * @package App\Business\Vendas
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaBusiness
{

    private EntityManagerInterface $doctrine;

    private VendaEntityHandler $vendaEntityHandler;

    private CrosierEntityIdAPIClient $crosierEntityIdAPIClient;

    private LoggerInterface $logger;


    public function __construct(EntityManagerInterface $doctrine,
                                VendaEntityHandler $vendaEntityHandler,
                                CrosierEntityIdAPIClient $crosierEntityIdAPIClient,
                                LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->vendaEntityHandler = $vendaEntityHandler;
        $this->crosierEntityIdAPIClient = $crosierEntityIdAPIClient;
        $this->logger = $logger;
    }


    /**
     *
     * @param Venda $venda
     * @return Venda
     */
    public function recalcularTotais(Venda $venda): Venda
    {
        $bdTotalItens = 0.0;
        foreach ($venda->itens as $item) {
            $bdTotalItens += $item->total;
        }
        $totalVenda = $bdTotalItens - abs($venda->desconto);
        $venda->subtotal = $bdTotalItens;
        $venda->valorTotal = $totalVenda;

        $this->doctrine->persist($venda);
        $this->doctrine->flush();
        return $venda;
    }

}