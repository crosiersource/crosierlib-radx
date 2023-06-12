<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Grupo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;

/**
 * Repository para a entidade Grupo.
 *
 * @author Carlos Eduardo Pauluk
 */
class GrupoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Grupo::class;
    }

    public function findUltimoItemDoGrupo(Grupo $grupo): ?GrupoItem
    {
        $sql = 'SELECT max(id) as ultimoId FROM fin_grupo_item WHERE grupo_pai_id = :grupoId';
        $rs = $this->doctrine->getConnection()->fetchAssociative($sql, ['grupoId' => $grupo->getId()]);
        if ($rs) {
            $repoGrupoItem = $this->doctrine->getRepository(GrupoItem::class);
            return $repoGrupoItem->find($rs['ultimoId']);
        }
        return null;
    }

}
