<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Ecommerce;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Ecommerce\MercadoLivreItem;

/**
 * @author Carlos Eduardo Pauluk
 */
class MercadoLivreItemRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return MercadoLivreItem::class;
    }
}
