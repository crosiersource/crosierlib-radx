<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegistroConferencia;

/**
 * Class RegistroConferenciaEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class RegistroConferenciaEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return RegistroConferencia::class;
    }

}