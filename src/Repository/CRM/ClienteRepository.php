<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\CRM;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ClienteRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Cliente::class;
    }


}
