<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CaixaOperacao;

/**
 * Repository para a entidade CaixaOperacao.
 *
 * @author Carlos Eduardo Pauluk
 */
class CaixaOperacaoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return CaixaOperacao::class;
    }


    public function getDtCaixaAberto(Carteira $carteira): ?\DateTime
    {
        $sql = 'SELECT operacao, dt_operacao FROM fin_caixa_operacao WHERE carteira_id = :carteira ORDER BY dt_operacao DESC LIMIT 1';
        $rs = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, ['carteira' => $carteira->getId()]);
        if ($rs) {
            if ($rs[0]['operacao'] === 'ABERTURA') {
                return DateTimeUtils::parseDateStr($rs[0]['dt_operacao']);
            }
        }
        return null;
    }

}
