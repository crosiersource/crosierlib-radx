<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CaixaOperacao;
use Symfony\Component\Security\Core\Security;

/**
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class CaixaOperacaoEntityHandler extends EntityHandler
{

    protected bool $isTransacionalSave = true;

    
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
        if ($caixaOperacao->carteira->caixaStatus === 'ABERTO' && $caixaOperacao === 'ABERTURA') {
            throw new ViewException("Caixa já está aberto.");
        }
        if ($caixaOperacao->carteira->caixaStatus === 'FECHADO' && $caixaOperacao === 'FECHAMENTO') {
            throw new ViewException("Caixa já está fechado.");
        }
        if (!in_array("ROLE_FINAN_CAIXAOPERACAO", $caixaOperacao->responsavel->getRoles(), true)) {
            throw new ViewException("Usuário sem permissão para abrir/fechar caixa.");
        }
        
        $caixaOperacao->carteira->caixaStatus = $caixaOperacao->operacao === 'ABERTURA' ? 'ABERTO' : 'FECHADO';
        $caixaOperacao->carteira->caixaResponsavel = $caixaOperacao->responsavel;
    }


}