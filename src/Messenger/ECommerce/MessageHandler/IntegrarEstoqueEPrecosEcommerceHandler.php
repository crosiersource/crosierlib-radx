<?php


namespace CrosierSource\CrosierLibRadxBundle\Messenger\ECommerce\MessageHandler;


use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibRadxBundle\Business\ECommerce\IntegradorECommerceFactory;
use CrosierSource\CrosierLibRadxBundle\Messenger\ECommerce\Message\IntegrarEstoqueEPrecosEcommerceMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * @author Carlos Eduardo Pauluk
 */
class IntegrarEstoqueEPrecosEcommerceHandler implements MessageHandlerInterface
{

    private SyslogBusiness $syslog;

    private EntityManagerInterface $doctrine;

    private IntegradorECommerceFactory $integradorBusinessFactory;


    public function __construct(SyslogBusiness $syslog, EntityManagerInterface $doctrine, IntegradorECommerceFactory $integradorBusinessFactory)
    {
        $this->syslog = $syslog->setApp('radx')->setComponent(self::class);
        $this->doctrine = $doctrine;
        $this->integradorBusinessFactory = $integradorBusinessFactory;
    }

    /**
     * Consumidor das mensagems IntegrarProdutoEcommerceMessage
     *
     * @param IntegrarEstoqueEPrecosEcommerceMessage $message
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function __invoke(IntegrarEstoqueEPrecosEcommerceMessage $message)
    {
        $this->syslog->info('queue: consumindo IntegrarEstoqueEPrecosEcommerceMessage', implode(',', $message->produtosIds));
        $integrador = $this->integradorBusinessFactory->getIntegrador();
        $integrador->atualizaEstoqueEPrecos($message->produtosIds);
    }
}