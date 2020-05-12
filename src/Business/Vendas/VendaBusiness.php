<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Vendas;

use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaEntityHandler;
use Doctrine\ORM\EntityManagerInterface;

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


    public function __construct(EntityManagerInterface $doctrine,
                                VendaEntityHandler $vendaEntityHandler)
    {
        $this->doctrine = $doctrine;
        $this->vendaEntityHandler = $vendaEntityHandler;
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