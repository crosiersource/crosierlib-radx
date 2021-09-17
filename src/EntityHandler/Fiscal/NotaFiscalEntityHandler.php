<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibRadxBundle\Business\Fiscal\NFeUtils;
use CrosierSource\CrosierLibRadxBundle\Business\Fiscal\NotaFiscalBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use Doctrine\Common\Collections\ArrayCollection;
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

    public NFeUtils $nfeUtils;

    /**
     * NotaFiscalEntityHandler constructor.
     *
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param SyslogBusiness $syslog
     * @param ContainerInterface $container
     * @param NFeUtils $nfeUtils
     */
    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness $syslog,
                                ContainerInterface $container,
                                NFeUtils $nfeUtils)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->container = $container;
        $this->nfeUtils = $nfeUtils;
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
        if (!$notaFiscal->entradaSaida) {
            throw new ViewException('Entrada/Saída não informado');
        }
        
        if ($notaFiscal->getItens() && $notaFiscal->getItens()->count() > 0) {
            $this->calcularTotais($notaFiscal);
        }

        $i = 1;
        if ($notaFiscal->getItens()) {
            foreach ($notaFiscal->getItens() as $item) {
                $item->setOrdem($i++);
            }
        }

        $notaFiscal->setDocumentoEmitente(preg_replace("/[\D]/", '', $notaFiscal->getDocumentoEmitente()));
        $notaFiscal->setDocumentoDestinatario(preg_replace("/[\D]/", '', $notaFiscal->getDocumentoDestinatario()));
        $notaFiscal->setTranspDocumento(preg_replace("/[\D]/", '', $notaFiscal->getTranspDocumento()));

        if ($notaFiscal->getChaveAcesso() === '') {
            $notaFiscal->setChaveAcesso(null);
        }

        $notaFiscalBusiness = $this->container->get(NotaFiscalBusiness::class);

        if ($notaFiscalBusiness->isCnpjEmitente($notaFiscal->getDocumentoEmitente())) {
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
        $this->calcularTotais($notaFiscal);
    }


    /**
     * @param NotaFiscal $antiga
     * @throws \Exception
     */
    public function doClone($antiga)
    {
        $antiga = $this->getDoctrine()->getRepository(NotaFiscal::class)->findOneBy(['id' => $antiga->getId()]);
        $notaFiscalBusiness = $this->container->get(NotaFiscalBusiness::class);
        $notaFiscalItemEntityHandler = $notaFiscalBusiness->notaFiscalItemEntityHandler;

        $this->getDoctrine()->beginTransaction();

        try {
            $nova = clone $antiga;
            $nova->setId(null);
            $nova->setInserted(null);
            $nova->setUpdated(null);
            $nova->setUserInsertedId(null);
            $nova->setUserUpdatedId(null);
            $nova->setEstabelecimentoId(null);
            $nova->setUuid(null);
            $nova->setNumero(null);
            $nova->setSerie(null);
            $nova->setRandFaturam(null);
            $nova->setChaveAcesso(null);
            $nova->setDtEmissao(new \DateTime());
            $nova->setDtSaiEnt(null);
            $nova->setCStat(null);
            $nova->setCStatLote(null);
            $nova->setXMotivo(null);
            $nova->setXMotivoLote(null);
            $nova->setCnf(null);
            $nova->setMotivoCancelamento(null);
            $nova->setProtocoloAutorizacao(null);
            $nova->setNRec(null);
            $nova->setProtocoloAutorizacao(null);
            $nova->setDtProtocoloAutorizacao(null);
            $nova->setXmlNota(null);
            $nova->setResumo(null);
            $nova->setItens(new ArrayCollection());
            $nova->setHistoricos(new ArrayCollection());
            $this->save($nova, false);
            if ($antiga->getItens()) {
                foreach ($antiga->getItens() as $oldItem) {
                    /** @var NotaFiscalItem $newItem */
                    $newItem = clone $oldItem;
                    $newItem->setId(null);
                    $newItem->setInserted(new \DateTime());
                    $newItem->setUserInsertedId($this->security->getUser()->getId());
                    $newItem->setNotaFiscal($nova);
                    $nova->getItens()->add($newItem);
                    $notaFiscalItemEntityHandler->save($newItem, false);
                }
            }
            $this->save($nova);
            $this->getDoctrine()->commit();
            return $nova;
        } catch (\Throwable $e) {
            $this->getDoctrine()->rollback();
            $this->syslog->err('Erro ao clonar nota', $e->getMessage());
            throw new ViewException('Erro ao clonar nota', 0, $e->getMessage());
        }

    }

    public function afterClone($nova, $antiga)
    {

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
        if ($notaFiscal->getItens()) {
            foreach ($notaFiscal->getItens() as $item) {
                $item->calculaTotais();
                $subTotal = bcadd($subTotal, DecimalUtils::round($item->getSubTotal()), 2);
                $descontos = bcadd($descontos, DecimalUtils::round($item->getValorDesconto() ? $item->getValorDesconto() : 0.0), 2);
            }
        }
        $notaFiscal->setSubTotal($subTotal);
        $notaFiscal->setTotalDescontos($descontos);
        $notaFiscal->setValorTotal($subTotal - $descontos);
    }

}
