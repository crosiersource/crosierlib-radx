<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalCartaCorrecao;

/**
 * Class NotaFiscalCartaCorrecaoEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalCartaCorrecaoEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return NotaFiscalCartaCorrecao::class;
    }


    /**
     * @param $cartaCorrecao
     * @return mixed|void
     * @throws ViewException
     */
    public function beforeSave($cartaCorrecao)
    {
        /** @var NotaFiscalCartaCorrecao $cartaCorrecao */

        if (!$cartaCorrecao->getCartaCorrecao()) {
            throw new ViewException('É necessário informar a mensagem');
        }
        if (!$cartaCorrecao->getDtCartaCorrecao()) {
            throw new ViewException('É necessário informar a data/hora');
        }


        try {
            $conn = $this->getDoctrine()->getConnection();
            $sql = 'SELECT id, seq FROM fis_nf_cartacorrecao WHERE nota_fiscal_id = :notaFiscalId ORDER BY seq DESC LIMIT 1';
            $rsUltSeq = $conn->fetchAssociative($sql, ['notaFiscalId' => $cartaCorrecao->getNotaFiscal()->getId()]);
            if ((int)($rsUltSeq['id'] ?? -1) !== $cartaCorrecao->getId()) {
                $cartaCorrecao->setSeq($rsUltSeq['seq'] + 1);
            }
        } catch (\Throwable $e) {
            throw new ViewException('Erro ao incrementar seq da carta de correção');
        }


    }


}