<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\RH;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\RH\Cargo;

/**
 * Repository para a entidade Cargo.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class CargoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Cargo::class;
    }

}
