<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Ecommerce;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Ecommerce\ClienteConfig;

/**
 * @author Carlos Eduardo Pauluk
 */
class ClienteConfigRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return ClienteConfig::class;
    }
}
