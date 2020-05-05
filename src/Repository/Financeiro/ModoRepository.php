<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;

/**
 * Repository para a entidade Modo.
 *
 * @author Carlos Eduardo Pauluk
 */
class ModoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Modo::class;
    }
}
