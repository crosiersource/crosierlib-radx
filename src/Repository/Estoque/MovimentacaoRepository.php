<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Movimentacao;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class MovimentacaoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Movimentacao::class;
    }

}
