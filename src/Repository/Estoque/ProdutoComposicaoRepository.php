<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoComposicao;

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
