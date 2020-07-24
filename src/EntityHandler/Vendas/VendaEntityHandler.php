<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibRadxBundle\Business\Vendas\VendaBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class VendaEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaEntityHandler extends EntityHandler
{

    private VendaBusiness $vendaBusiness;

    /**
     * VendaItemEntityHandler constructor.
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param SyslogBusiness $syslog
     * @param VendaBusiness $vendaBusiness
     */
    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness $syslog,
                                VendaBusiness $vendaBusiness)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->vendaBusiness = $vendaBusiness;
    }

    public function beforeSave(/** @var Venda $venda */ $venda)
    {
        if ($venda->jsonData['ecommerce_status'] ?? false) {
            /** @var AppConfigRepository $repoAppConfig */
            $repoAppConfig = $this->getDoctrine()->getRepository(AppConfig::class);
            $jsonMetadata = json_decode($repoAppConfig->findByChave('ven_venda_json_metadata'), true);
            if (!($jsonMetadata['campos']['ecommerce_status']['sugestoes'][$venda->jsonData['ecommerce_status']] ?? false)) {
                throw new \RuntimeException('ecommerce_status N/D');
            }
            $venda->jsonData['ecommerce_status_descricao'] = $jsonMetadata['campos']['ecommerce_status']['sugestoes'][$venda->jsonData['ecommerce_status']];
        }
        $venda->subtotal = $venda->subtotal ?? 0.0;
        $venda->desconto = $venda->desconto ?? 0.0;
        $venda->valorTotal = $venda->valorTotal ?? 0.0;
    }

    public function getEntityClass(): string
    {
        return Venda::class;
    }
}