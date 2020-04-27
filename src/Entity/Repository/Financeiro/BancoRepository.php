<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Banco;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 * Repository para a entidade Banco.
 *
 * @author Carlos Eduardo Pauluk
 */
class BancoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Banco::class;
    }
}
