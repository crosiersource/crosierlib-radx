<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\MovimentacaoItem;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class MovimentacaoItemRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return MovimentacaoItem::class;
    }
}
