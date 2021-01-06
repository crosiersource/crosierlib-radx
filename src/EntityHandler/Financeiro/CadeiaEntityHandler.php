<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Cadeia;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Class CadeiaEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class CadeiaEntityHandler extends EntityHandler
{

    private MovimentacaoEntityHandler $movimentacaoEntityHandler;

    /**
     * @required
     * @param MovimentacaoEntityHandler $movimentacaoEntityHandler
     */
    public function setMovimentacaoEntityHandler(MovimentacaoEntityHandler $movimentacaoEntityHandler): void
    {
        $this->movimentacaoEntityHandler = $movimentacaoEntityHandler;
    }


    public function getEntityClass()
    {
        return Cadeia::class;
    }

    /**
     *
     * @param Cadeia $cadeia
     * @throws ViewException
     */
    public function deleteCadeiaETodasAsMovimentacoes(Cadeia $cadeia): void
    {
        try {
            $this->doctrine->beginTransaction();
            $movs = $cadeia->movimentacoes;
            /** @var Movimentacao $mov */
            foreach ($movs as $mov) {
                $this->movimentacaoEntityHandler->delete($mov);
            }
            $this->delete($cadeia);
            $this->doctrine->commit();
        } catch (\Throwable $e) {
            $this->doctrine->rollback();
            $err = $e->getMessage();
            if (isset($mov)) {
                $err .= ' (' . $mov->descricao . ')';
            }
            throw new ViewException($err);
        }
    }

    /**
     *
     */
    public function removerCadeiasComApenasUmaMovimentacao(): void
    {
        $rsm = new ResultSetMapping();
        $sql = 'select id, cadeia_id, count(cadeia_id) as qt from fin_movimentacao group by cadeia_id having qt < 2';
        $qry = $this->getDoctrine()->createNativeQuery($sql, $rsm);

        $rsm->addScalarResult('id', 'id');
        $rs = $qry->getResult();
        if ($rs) {
            foreach ($rs as $r) {
                /** @var Movimentacao $movimentacao */
                $movimentacao = $this->getDoctrine()->find(Movimentacao::class, $r['id']);
                if ($movimentacao->cadeia) {
                    $cadeia = $this->getDoctrine()->find(Cadeia::class, $movimentacao->cadeia);
                    $movimentacao->cadeia = null;
                    $this->getDoctrine()->remove($cadeia);
                }
            }
        }
        $this->getDoctrine()->flush();
    }


}
