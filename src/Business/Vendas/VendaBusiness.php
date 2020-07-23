<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Vendas;

use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
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
     * @throws ViewException
     */
    public function recalcularTotais(int $vendaId): void
    {
        $conn = $this->doctrine->getConnection();
        $rsTotais = $conn->fetchAll('SELECT sum(subtotal) as subtotal, sum(desconto) as desconto, sum(total) as total FROM ven_venda_item WHERE venda_id = :vendaId', ['vendaId' => $vendaId]);

        if (!$rsTotais) {
            throw new ViewException('Erro ao buscar totais da venda');
        }
        $venda = [];
        $venda['subtotal'] = $rsTotais[0]['subtotal'] ?? 0.0;
        $venda['desconto'] = $rsTotais[0]['desconto'] ?? 0.0;
        $venda['valor_total'] = $rsTotais[0]['total'] ?? 0.0;



        $conn->update('ven_venda', $venda, ['id' => $vendaId]);
    }

}