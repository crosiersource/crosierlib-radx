<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\Cte;

/**
 * @author Carlos Eduardo Pauluk
 */
class CteEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return Cte::class;
    }
}
