<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CaixaOperacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;

/**
 * Class CarteiraEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class CarteiraEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return Carteira::class;
    }

    /**
     * @param Carteira $carteira
     * @return mixed|void
     */
    public function beforeSave($carteira)
    {
        if (!$carteira->dtConsolidado) {
            $carteira->dtConsolidado = DateTimeUtils::parseDateStr('1900-01-01');
        }

        if (!$carteira->codigo) {
            $rs = $this->getDoctrine()->getConnection()->fetchAllAssociative('SELECT MAX(codigo) as max FROM fin_carteira');
            $carteira->codigo = (int)$rs[0]['max'] + 1;
        }
    }

    /**
     * @param Carteira $carteira
     * @return mixed|void
     */
    public function afterSave($carteira)
    {
        $this->getDoctrine()->getConnection()->executeStatement('DELETE FROM fin_saldo WHERE carteira_id = :carteiraId', ['carteiraId' => $carteira->getId()]);
    }


}
