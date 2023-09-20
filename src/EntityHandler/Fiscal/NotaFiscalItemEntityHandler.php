<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class NotaFiscalItemEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalItemEntityHandler extends EntityHandler
{

    private NotaFiscalEntityHandler $notaFiscalEntityHandler;


    public function __construct(ManagerRegistry         $doctrine,
                                Security                $security,
                                ParameterBagInterface   $parameterBag,
                                SyslogBusiness          $syslog,
                                NotaFiscalEntityHandler $notaFiscalEntityHandler)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->notaFiscalEntityHandler = $notaFiscalEntityHandler;
    }


    /**
     * @param NotaFiscalItem $nfItem
     * @return mixed|void
     */
    public function beforeSave($nfItem)
    {
        if ($nfItem->valorUnit === null) {
            throw new ViewException('Item sem valor unitário');
        }
        /** @var NotaFiscalItem $nfItem */
        if (!$nfItem->ordem) {
            $ultimaOrdem = 0;
            if ($nfItem->notaFiscal->getItens()) {
                foreach ($nfItem->notaFiscal->getItens() as $item) {
                    if ($item->ordem > $ultimaOrdem) {
                        $ultimaOrdem = $item->ordem;
                    }
                }
            }
            $nfItem->ordem = $ultimaOrdem + 1;
        }

        $nfeConfigs = [];
        $cnpjsProprios = $this->notaFiscalEntityHandler->nfeUtils->getNFeConfigsCNPJs();
        if (in_array(($nfItem->notaFiscal->documentoEmitente ?? ''), $cnpjsProprios, true)) {
            $nfeConfigs = $this->notaFiscalEntityHandler->nfeUtils->getNFeConfigsByCNPJ($nfItem->notaFiscal->documentoEmitente);
        }

        if (!$nfItem->csosn) {
            if ($nfeConfigs['CSOSN'] ?? false) {
                $nfItem->csosn = $nfeConfigs['CSOSN'];
            }
        }
        if (!$nfItem->cst) {
            if ($nfeConfigs['CST'] ?? false) {
                $nfItem->cst = $nfeConfigs['CST'];
            }
        }

        $nfItem->subtotal = $nfItem->subtotal ?? 0.0;
        
        $nfItem->pisValor = bcmul($nfItem->pisValorBc / 100.0, $nfItem->pisAliquota, 2);
        $nfItem->icmsValor = bcmul($nfItem->icmsValorBc / 100.0, $nfItem->icmsAliquota, 2);
        $nfItem->cofinsValor = bcmul($nfItem->cofinsValorBc / 100.0, $nfItem->cofinsAliquota, 2);
        
        $nfItem->calculaTotais();
    }

    /**
     * @param $nfItem
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function afterSave(/** @var NotaFiscalItem $nfItem */ $nfItem)
    {
        $this->notaFiscalEntityHandler->save($nfItem->notaFiscal);
    }

    /**
     * @param $nfItem
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function afterDelete(/** @var NotaFiscalItem $nfItem */ $nfItem)
    {
        if ($nfItem->notaFiscal) {
            /** @var NotaFiscal $notaFiscal */
            $notaFiscal = $this->getDoctrine()->getRepository(NotaFiscal::class)->findOneBy(['id' => $nfItem->notaFiscal->getId()]);
            $this->notaFiscalEntityHandler->calcularTotais($notaFiscal);
            $this->notaFiscalEntityHandler->save($notaFiscal);
        }
    }

    /**
     * @return mixed|string
     */
    public function getEntityClass()
    {
        return NotaFiscalItem::class;
    }

}
