<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CaixaOperacao;

/**
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class CaixaOperacaoEntityHandler extends EntityHandler
{

    public function getEntityClass()
    {
        return CaixaOperacao::class;
    }

    /**
     * @param CaixaOperacao $caixaOperacao
     * @return mixed|void
     */
    public function beforeSave($caixaOperacao)
    {
        if (!$caixaOperacao->UUID) {
            $caixaOperacao->UUID = StringUtils::guidv4();
        }
    }


}