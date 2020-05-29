<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Vendas;

use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
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

    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }


    /**
     *
     * @param Venda $venda
     * @return Venda
     */
    public function recalcularTotais(Venda $venda): Venda
    {
        $totalSubtotais = 0.0;
        $totalDescontos = 0.0;
        foreach ($venda->itens as $item) {
            $totalSubtotais += $item->subtotal;
            $totalDescontos += $item->desconto;
        }
        $venda->subtotal = $totalSubtotais;
        $venda->desconto = $totalDescontos;
        $venda->getValorTotal();
        return $venda;
    }

}