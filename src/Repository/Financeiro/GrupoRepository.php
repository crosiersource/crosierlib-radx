<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Grupo;

/**
 * Repository para a entidade Grupo.
 *
 * @author Carlos Eduardo Pauluk
 */
class GrupoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Grupo::class;
    }
}
