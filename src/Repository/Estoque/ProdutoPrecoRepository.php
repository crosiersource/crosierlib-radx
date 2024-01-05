<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoPreco;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ProdutoPrecoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return ProdutoPreco::class;
    }

    public function findPrecoEmDataVenda(Produto $produto, $dtVenda): ?ProdutoPreco
    {
        $ql = "SELECT pp FROM CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoPreco pp JOIN CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto p WHERE pp.produto = p AND p = :produto AND pp.dtPrecoVenda <= :dtVenda ORDER BY pp.inserted DESC";
        $query = $this->getEntityManager()->createQuery($ql);
        $query->setParameters(array(
            'produto' => $produto,
            'dtVenda' => $dtVenda
        ));
        $query->setMaxResults(1);
        $results = $query->getResult();
        return count($results) >= 1 ? $results[0] : null;
    }

}
