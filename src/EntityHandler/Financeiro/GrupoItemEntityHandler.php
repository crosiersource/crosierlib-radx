<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;

/**
 * Class GrupoItemEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class GrupoItemEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return GrupoItem::class;
    }

    public function beforeSave(/** @var GrupoItem $grupoItem */ $grupoItem)
    {
        if (!$grupoItem->descricao) {
            $grupoItem->descricao = $grupoItem->pai->descricao . ' - ' . $grupoItem->dtVencto->format('d/m/Y');
        }
        if (!$grupoItem->carteiraPagante) {
            $repoCarteira = $this->doctrine->getRepository(Carteira::class);
            $indefinida = $repoCarteira->findOneByCodigo(99);
            $grupoItem->carteiraPagante = $indefinida;
        }
    }


}