<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CaixaOperacao;

/**
 * Repository para a entidade CaixaOperacao.
 *
 * @author Carlos Eduardo Pauluk
 */
class CaixaOperacaoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return CaixaOperacao::class;
    }

}
