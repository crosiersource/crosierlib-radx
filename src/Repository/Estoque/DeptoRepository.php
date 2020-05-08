<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class DeptoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Depto::class;
    }

}
