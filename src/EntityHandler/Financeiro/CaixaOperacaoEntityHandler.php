<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CaixaOperacao;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class CaixaOperacaoEntityHandler extends EntityHandler
{

    protected bool $isTransacionalSave = true;
    

    public CarteiraEntityHandler $carteiraEntityHandler;


    public function __construct(ManagerRegistry       $doctrine,
                                Security              $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness        $syslog,
                                CarteiraEntityHandler $carteiraEntityHandler)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog);
        $this->carteiraEntityHandler = $carteiraEntityHandler;
    }

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
        if (!in_array($caixaOperacao->operacao, ["ABERTURA", "FECHAMENTO"], true)) {
            throw new ViewException("Operação deve ser 'ABERTURA' ou 'FECHAMENTO'");
        }
        if (!in_array("ROLE_FINAN_CAIXAOPERACAO", $caixaOperacao->responsavel->getRoles(), true)) {
            throw new ViewException("Usuário sem permissão para abrir/fechar caixa.");
        }
    }

    /**
     * @param CaixaOperacao $caixaOperacao
     * @return mixed|void
     */
    public function afterSave($caixaOperacao)
    {
        $caixaOperacao->carteira->caixaStatus = $caixaOperacao->operacao === 'ABERTURA' ? 'ABERTO' : 'FECHADO';
        $caixaOperacao->carteira->caixaResponsavel = $caixaOperacao->responsavel;
        $caixaOperacao->carteira->caixaDtUltimaOperacao = $caixaOperacao->dtOperacao;
        $this->carteiraEntityHandler->save($caixaOperacao->carteira);
    }


}
