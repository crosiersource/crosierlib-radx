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
                                Security               $security,
                                ParameterBagInterface  $parameterBag,
                                SyslogBusiness         $syslog,
                                ContainerInterface     $container,
                                NFeUtils               $nfeUtils)
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
                if ($item->ordem !== $i) {
                    $item->ordem = $i;
                }
                $i++;
            }
        }

        $notaFiscal->documentoEmitente = (preg_replace("/[\D]/", '', $notaFiscal->documentoEmitente));
        $notaFiscal->documentoDestinatario = (preg_replace("/[\D]/", '', $notaFiscal->documentoDestinatario));
        $notaFiscal->transpDocumento = (preg_replace("/[\D]/", '', $notaFiscal->transpDocumento));

        if ($notaFiscal->chaveAcesso === '') {
            $notaFiscal->chaveAcesso = (null);
        }

        $notaFiscalBusiness = $this->container->get(NotaFiscalBusiness::class);

        if ($notaFiscalBusiness->isCnpjEmitente($notaFiscal->documentoEmitente)) {
            $arrEmitente = $notaFiscalBusiness->getEmitenteFromNFeConfigsByCNPJ($notaFiscal->documentoEmitente);

            $notaFiscal->xNomeEmitente = ($arrEmitente['razaosocial']);
            $notaFiscal->inscricaoEstadualEmitente = ($arrEmitente['ie']);
            $notaFiscal->logradouroEmitente = ($arrEmitente['logradouro']);
            $notaFiscal->numeroEmitente = ($arrEmitente['numero']);
            $notaFiscal->bairroEmitente = ($arrEmitente['bairro']);
            $notaFiscal->cepEmitente = ($arrEmitente['cep']);
            $notaFiscal->cidadeEmitente = ($arrEmitente['cidade']);
            $notaFiscal->estadoEmitente = ($arrEmitente['estado']);
            $notaFiscal->foneEmitente = ($arrEmitente['fone1']);
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
            $nova->uuid = null;
            $nova->numero = null;
            $nova->serie = null;
            $nova->randFaturam = null;
            $nova->chaveAcesso = null;
            $nova->dtEmissao = new \DateTime();
            $nova->dtSaiEnt = null;
            $nova->cStat = null;
            $nova->cStatLote = null;
            $nova->xMotivo = null;
            $nova->xMotivoLote = null;
            $nova->cnf = null;
            $nova->motivoCancelamento = null;
            $nova->protocoloAutorizacao = null;
            $nova->nRec = null;
            $nova->protocoloAutorizacao = null;
            $nova->dtProtocoloAutorizacao = null;
            $nova->setXmlNota(null);
            $nova->resumo = null;
            $nova->itens = new ArrayCollection();
            $nova->historicos = new ArrayCollection();
            $this->save($nova, false);
            if ($antiga->getItens()) {
                foreach ($antiga->getItens() as $oldItem) {
                    /** @var NotaFiscalItem $newItem */
                    $newItem = clone $oldItem;
                    $newItem->setId(null);
                    $newItem->setInserted(new \DateTime());
                    $newItem->setUserInsertedId($this->security->getUser()->getId());
                    $newItem->notaFiscal = $nova;
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
            $item->notaFiscal = null;
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
                $subTotal = bcadd($subTotal, DecimalUtils::round($item->subtotal), 2);
                $descontos = bcadd($descontos, DecimalUtils::round($item->valorDesconto ? $item->valorDesconto : 0.0), 2);
            }
        }
        if ((float)$notaFiscal->subtotal !== (float)$subTotal) {
            $notaFiscal->subtotal = ((float)$subTotal);
        }
        if ((float)$notaFiscal->totalDescontos !== (float)$descontos) {
            $notaFiscal->totalDescontos = ((float)$descontos);
        }
        $valorTotal = (float)(bcsub($subTotal, $descontos, 2));
        if ((float)$notaFiscal->valorTotal !== (float)$valorTotal) {
            $notaFiscal->valorTotal = ($valorTotal);
        }
    }

}
