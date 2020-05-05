<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Banco;

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
