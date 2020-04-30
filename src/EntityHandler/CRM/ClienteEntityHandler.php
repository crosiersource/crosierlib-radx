<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;

/**
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM
 * @author Carlos Eduardo Pauluk
 */
class ClienteEntityHandler extends EntityHandler
{


    public function getEntityClass()
    {
        return Cliente::class;
    }
}