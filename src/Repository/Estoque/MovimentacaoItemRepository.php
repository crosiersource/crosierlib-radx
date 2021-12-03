<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\MovimentacaoItem;

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
