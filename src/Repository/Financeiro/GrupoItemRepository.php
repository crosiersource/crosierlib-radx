<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Grupo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;

/**
 * Repository para a entidade GrupoItem.
 *
 * @author Carlos Eduardo Pauluk
 */
class GrupoItemRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return GrupoItem::class;
    }

    public function findAllByMesAno(\DateTime $mesAno): array
    {
        return $this->findAllByFiltersSimpl([
            ['dtVencto', 'BETWEEN_MESANO', $mesAno]
        ]);
    }

    public function findByMesAnoAndGrupo(\DateTime $mesAno, Grupo $grupo): ?GrupoItem
    {
        try {
            return $this->findOneByFiltersSimpl([
                ['dtVencto', 'BETWEEN_MESANO', $mesAno],
                ['pai', 'EQ', $grupo]
            ]);
        } catch (ViewException $e) {
            throw new ViewException('Existem dois registros para o grupo ' . $grupo->descricao . ' no mês ' . $mesAno->format('m/Y') . '.');
        }
    }

    public function findByDtMoviment(Grupo $grupo, \DateTime $dtMoviment): ?GrupoItem
    {
        $mesAno = ($dtMoviment->format('d') >= $grupo->diaInicioAprox) ? DateTimeUtils::incMes($dtMoviment) : $dtMoviment;
        return $this->findOneByFiltersSimpl([
            ['dtVencto', 'BETWEEN_MESANO', $mesAno],
            ['pai', 'EQ', $grupo]
        ]);
    }
}
