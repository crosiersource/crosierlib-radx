<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CaixaOperacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;

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

    public function getUltimaOperacao(Carteira $carteira): ?CaixaOperacao
    {
        return $this->findOneByFiltersSimpl([['carteira', 'EQ', $carteira->getId()]], ['dtOperacao' => 'DESC']);
    }

    public function getDtCaixaAberto(Carteira $carteira): ?\DateTime
    {
        $ultimaOperacao = $this->getUltimaOperacao($carteira);
        if ($ultimaOperacao) {
            if ($ultimaOperacao->operacao === 'ABERTURA') {
                return $ultimaOperacao->dtOperacao;
            }
        }
        return null;
    }

}
