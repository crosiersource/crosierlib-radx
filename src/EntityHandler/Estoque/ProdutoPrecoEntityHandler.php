<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoPreco;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class ProdutoPrecoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return ProdutoPreco::class;
    }

    public function afterSave(/** @var ProdutoPreco $produtoPreco * */ $produtoPreco)
    {
        if ($produtoPreco->atual) {
            // Só pode ter 1 preço marcado como 'atual' para o mesmo produto, lista e unidade
            $conn = $this->getDoctrine()->getConnection();
            $conn->executeUpdate('UPDATE est_produto_preco SET atual = 0 WHERE id != :id AND produto_id = :produtoId AND lista_id = :listaId AND unidade_id = :unidadeId',
                [
                    'id' => $produtoPreco->getId(),
                    'produtoId' => $produtoPreco->produto->getId(),
                    'listaId' => $produtoPreco->lista->getId(),
                    'unidadeId' => $produtoPreco->unidade->getId()
                ]);
        }
    }


}