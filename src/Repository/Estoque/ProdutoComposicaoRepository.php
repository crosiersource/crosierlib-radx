<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoComposicao;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ProdutoComposicaoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return ProdutoComposicao::class;
    }

}
