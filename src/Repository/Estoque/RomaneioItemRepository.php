<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\RomaneioItem;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class RomaneioItemRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return RomaneioItem::class;
    }
}
