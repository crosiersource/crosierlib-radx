<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibRadxBundle\Business\Fiscal\NotaFiscalBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class NotaFiscalEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalEntityHandler extends EntityHandler
{

    public ContainerInterface $container;

    /**
     * NotaFiscalEntityHandler constructor.
     *
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param SyslogBusiness $syslog
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness $syslog,
                                ContainerInterface $container)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->container = $container;
    }


    public function getEntityClass()
    {
        return NotaFiscal::class;
    }

    /**
     * @param $notaFiscal
     * @return mixed|void
     */
    public function beforeSave(/** @var NotaFiscal $notaFiscal */ $notaFiscal)
    {
        if ($notaFiscal->getItens() && $notaFiscal->getItens()->count() > 0) {
            $this->calcularTotais($notaFiscal);
        }

        $i = 1;
        foreach ($notaFiscal->getItens() as $item) {
            $item->setOrdem($i++);
        }

        $notaFiscal->setDocumentoEmitente(preg_replace("/[\D]/", '', $notaFiscal->getDocumentoEmitente()));
        $notaFiscal->setDocumentoDestinatario(preg_replace("/[\D]/", '', $notaFiscal->getDocumentoDestinatario()));
        $notaFiscal->setTranspDocumento(preg_replace("/[\D]/", '', $notaFiscal->getTranspDocumento()));

        if ($notaFiscal->getChaveAcesso() === '') {
            $notaFiscal->setChaveAcesso(null);
        }

        $notaFiscalBusiness = $this->container->get(NotaFiscalBusiness::class);

        $arrEmitente = $notaFiscalBusiness->getEmitenteFromNFeConfigsByCNPJ($notaFiscal->getDocumentoEmitente());

        $notaFiscal->setXNomeEmitente($arrEmitente['razaosocial']);
        $notaFiscal->setInscricaoEstadualEmitente($arrEmitente['ie']);
        $notaFiscal->setLogradouroEmitente($arrEmitente['logradouro']);
        $notaFiscal->setNumeroEmitente($arrEmitente['numero']);
        $notaFiscal->setBairroEmitente($arrEmitente['bairro']);
        $notaFiscal->setCepEmitente($arrEmitente['cep']);
        $notaFiscal->setCidadeEmitente($arrEmitente['cidade']);
        $notaFiscal->setEstadoEmitente($arrEmitente['estado']);
        $notaFiscal->setFoneEmitente($arrEmitente['fone1']);
    }

    /**
     * @param $notaFiscal
     * @throws \Exception
     */
    public function beforeClone($notaFiscal)
    {
        /** @var NotaFiscal $notaFiscal */
        $notaFiscal->setUuid(null);
        $notaFiscal->setNumero(null);
        $notaFiscal->setSerie(null);
        $notaFiscal->setRandFaturam(null);
        $notaFiscal->setChaveAcesso(null);
        $notaFiscal->setDtEmissao(new \DateTime());
        $notaFiscal->setDtSaiEnt(null);
        $notaFiscal->setCStat(null);
        $notaFiscal->setCStatLote(null);
        $notaFiscal->setXMotivo(null);
        $notaFiscal->setXMotivoLote(null);
        $notaFiscal->setCnf(null);
        $notaFiscal->setMotivoCancelamento(null);
        $notaFiscal->setProtocoloAutorizacao(null);
        $notaFiscal->setNRec(null);
        $notaFiscal->setProtocoloAutorizacao(null);
        $notaFiscal->setDtProtocoloAutorizacao(null);
        $notaFiscal->setXmlNota(null);
        $notaFiscal->setResumo(null);

        if ($notaFiscal->getItens() && $notaFiscal->getItens()->count() > 0) {
            $oldItens = clone $notaFiscal->getItens();
            $notaFiscal->getItens()->clear();
            foreach ($oldItens as $oldItem) {
                /** @var NotaFiscalItem $newItem */
                $newItem = clone $oldItem;
                $newItem->setId(null);
                $newItem->setInserted(new \DateTime());
                $newItem->setUserInsertedId($this->security->getUser()->getId());
                $newItem->setNotaFiscal($notaFiscal);
                $notaFiscal->getItens()->add($newItem);
            }
        }

        if ($notaFiscal->getHistoricos()) {
            $notaFiscal->getHistoricos()->clear();
        }
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function deleteAllItens(NotaFiscal $notaFiscal)
    {
        foreach ($notaFiscal->getItens() as $item) {
            $item->setNotaFiscal(null);
        }
        $notaFiscal->getItens()->clear();
        /** @var NotaFiscal $notaFiscal */
        $notaFiscal = $this->save($notaFiscal);
        return $notaFiscal;
    }

    /**
     * Calcula o total da nota e o total de descontos.
     *
     * @param
     *            nf
     */
    public function calcularTotais(NotaFiscal $notaFiscal): void
    {
        $subTotal = 0.0;
        $descontos = 0.0;
        foreach ($notaFiscal->getItens() as $item) {
            $item->calculaTotais();
            $subTotal = bcadd($subTotal, DecimalUtils::round($item->getSubTotal()), 2);
            $descontos = bcadd($descontos, DecimalUtils::round($item->getValorDesconto() ? $item->getValorDesconto() : 0.0), 2);
        }
        $notaFiscal->setSubTotal($subTotal);
        $notaFiscal->setTotalDescontos($descontos);
        $notaFiscal->setValorTotal($subTotal - $descontos);
    }

}