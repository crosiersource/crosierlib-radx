<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
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
    public function buildTreeList()
    {
        $sql = "SELECT id, codigo, concat(rpad('', 2*(length(codigo)-1),'.'), codigo, ' - ',  descricao) as descricaoMontada FROM fin_categoria ORDER BY codigo_ord";
        $em = $this->getEntityManager();
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param array $sel
     * @return false|string|void
     * @throws \Exception
     */
    public function getSelect2js($sel = [])
    {
        $rs = $this->getEntityManager()->getConnection()->fetchAll('SELECT id, codigo, descricao FROM fin_categoria ORDER BY codigo_ord');
        if (!is_array($sel)) {
            $sel = [$sel];
        }
        foreach ($rs as $e) {
            $r[] = [
                'id' => $e['id'],
                'text' => str_pad(StringUtils::mascarar($e['codigo'], Categoria::MASK), strlen($e['codigo'])*2, '.', STR_PAD_LEFT) . ' - ' . $e['descricao'],
                'selected' => in_array($e['id'], $sel) ? 'selected' : ''
            ];
        }
        return json_encode($r);
    }


}
