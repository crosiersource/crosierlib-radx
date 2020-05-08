<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\RH;

use CrosierSource\CrosierLibRadxBundle\Entity\RH\Cargo;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 * EntityHandler para a entidade Cargo.
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler
 * @author Carlos Eduardo Pauluk
 */
class CargoEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return Cargo::class;
    }
}