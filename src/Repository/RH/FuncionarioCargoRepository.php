<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\RH;

use CrosierSource\CrosierLibRadxBundle\Entity\RH\FuncionarioCargo;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 * Repository para a entidade FuncionarioCargo.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class FuncionarioCargoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return FuncionarioCargo::class;
    }

}
