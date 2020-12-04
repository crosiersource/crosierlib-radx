<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;

/**
 * Repository para a entidade Banco.
 *
 * @author Carlos Eduardo Pauluk
 */
class CategoriaRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Categoria::class;
    }

    /**
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function buildTreeList(?int $codigoSuper = null)
    {
        $where = '';
        $params = null;
        if  ($codigoSuper) {
            $where = ' WHERE codigo_super = :codigoSuper';
            $params['codigoSuper'] = $codigoSuper;
        }
        $sql = "SELECT id, codigo, concat(rpad('', 2*(length(codigo)-1),'.'), codigo, ' - ',  descricao) as descricaoMontada FROM fin_categoria $where ORDER BY codigo_ord";
        $conn = $this->getEntityManager()->getConnection();
        return $conn->fetchAllAssociative($sql, $params);
    }

    /**
     * @param array $sel
     * @return false|string|void
     * @throws \Exception
     */
    public function getSelect2js($sel = [], bool $somenteSelFolhas = true)
    {
        $rsCategorias = $this->findAll(['codigoOrd' => 'ASC']);
        if (!is_array($sel)) {
            $sel = [$sel];
        }
        foreach ($rsCategorias as $categoria) {
            $r[] = [
                'id' => $categoria->getId(),
                'text' => $categoria->getDescricaoMontadaTree(),
                'codigo' => $categoria->codigo,
                'codigoSuper' => $categoria->codigoSuper,
                'folha' => $categoria->subCategs->count() === 0,
                'selected' => in_array($categoria->getId(), $sel) ? 'selected' : '',
                'disabled' => ($somenteSelFolhas && $categoria->subCategs->count() > 0),
            ];
        }
        return json_encode($r);
    }


}
