<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Business\Fiscal\NotaFiscalBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalCartaCorrecao;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class NotaFiscalCartaCorrecaoEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalCartaCorrecaoEntityHandler extends EntityHandler
{

    private bool $enviando = false;
    
    protected bool $isTransacionalSave = true;

    public ContainerInterface $container;

    public function __construct(ManagerRegistry       $doctrine,
                                Security              $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness        $syslog,
                                ContainerInterface    $container)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->container = $container;
    }

    public function getEntityClass()
    {
        return NotaFiscalCartaCorrecao::class;
    }


    /**
     * @param NotaFiscalCartaCorrecao $cartaCorrecao
     * @return mixed|void
     * @throws ViewException
     */
    public function beforeSave($cartaCorrecao)
    {
        /** @var NotaFiscalCartaCorrecao $cartaCorrecao */

        if (!$cartaCorrecao->cartaCorrecao) {
            throw new ViewException('É necessário informar a mensagem');
        }
        if (!$cartaCorrecao->dtCartaCorrecao) {
            $cartaCorrecao->dtCartaCorrecao = new \DateTime();
        }

        try {
            $conn = $this->getDoctrine()->getConnection();
            $sql = 'SELECT id, seq FROM fis_nf_cartacorrecao WHERE nota_fiscal_id = :notaFiscalId ORDER BY seq DESC LIMIT 1';
            $rsUltSeq = $conn->fetchAssociative($sql, ['notaFiscalId' => $cartaCorrecao->notaFiscal->getId()]);
            if ((int)($rsUltSeq['id'] ?? 0) !== $cartaCorrecao->getId()) {
                $cartaCorrecao->seq = ($rsUltSeq['seq'] ?? 0) + 1;
            }
        } catch (\Throwable $e) {
            throw new ViewException('Erro ao incrementar seq da carta de correção');
        }
    }

    /**
     * @param NotaFiscalCartaCorrecao $cartaCorrecao
     * @return mixed|void
     * @throws ViewException
     */
    public function afterSave($cartaCorrecao)
    {
        if (!$this->enviando) {
            $this->enviando = true;
            $notaFiscalBusiness = $this->container->get(NotaFiscalBusiness::class);
            $notaFiscalBusiness->cartaCorrecao($cartaCorrecao);
        }
    }


}