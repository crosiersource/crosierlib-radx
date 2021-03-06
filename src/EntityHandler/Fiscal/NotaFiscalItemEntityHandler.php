<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use Doctrine\ORM\EntityManagerInterface;
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

    /** @var NotaFiscalEntityHandler */
    private NotaFiscalEntityHandler $notaFiscalEntityHandler;


    /**
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param SyslogBusiness $syslog
     * @param NotaFiscalEntityHandler $notaFiscalEntityHandler
     */
    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness $syslog,
                                NotaFiscalEntityHandler $notaFiscalEntityHandler)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->notaFiscalEntityHandler = $notaFiscalEntityHandler;
    }


    /**
     * @param $nfItem
     * @return mixed|void
     */
    public function beforeSave($nfItem)
    {
        if ($nfItem->valorUnit === null) {
            throw new ViewException('Item sem valor unitário');
        }
        /** @var NotaFiscalItem $nfItem */
        if (!$nfItem->getOrdem()) {
            $ultimaOrdem = 0;
            foreach ($nfItem->getNotaFiscal()->getItens() as $item) {
                if ($item->getOrdem() > $ultimaOrdem) {
                    $ultimaOrdem = $item->getOrdem();
                }
            }
            $nfItem->setOrdem($ultimaOrdem + 1);
        }

        if (!$nfItem->getCsosn()) {
            $cnpjsProprios = $this->notaFiscalEntityHandler->nfeUtils->getNFeConfigsCNPJs();
            if (in_array(($nfItem->getNotaFiscal()->getDocumentoEmitente() ?? ''), $cnpjsProprios, true)) {
                $nfeConfigs = $this->notaFiscalEntityHandler->nfeUtils->getNFeConfigsByCNPJ($nfItem->getNotaFiscal()->getDocumentoEmitente());
                if ($nfeConfigs['CSOSN'] ?? false) {
                    $nfItem->setCsosn($nfeConfigs['CSOSN']);
                }
            }
        }
        $nfItem->calculaTotais();
    }

    /**
     * @param $nfItem
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function afterSave(/** @var NotaFiscalItem $nfItem */ $nfItem)
    {

    }

    /**
     * @param $nfItem
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function afterDelete(/** @var NotaFiscalItem $nfItem */ $nfItem)
    {
        if ($nfItem->getNotaFiscal()) {
            /** @var NotaFiscal $notaFiscal */
            $notaFiscal = $this->getDoctrine()->getRepository(NotaFiscal::class)->findOneBy(['id' => $nfItem->getNotaFiscal()->getId()]);
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
