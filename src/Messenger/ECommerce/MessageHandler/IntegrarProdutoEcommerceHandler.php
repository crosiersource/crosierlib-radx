<?php


namespace CrosierSource\CrosierLibRadxBundle\Messenger\ECommerce\MessageHandler;


use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibRadxBundle\Business\ECommerce\IntegradorECommerceFactory;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Messenger\ECommerce\Message\IntegrarProdutoEcommerceMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * @author Carlos Eduardo Pauluk
 */
class IntegrarProdutoEcommerceHandler implements MessageHandlerInterface
{

    private SyslogBusiness $syslog;

    private EntityManagerInterface $doctrine;

    private IntegradorECommerceFactory $integradorBusinessFactory;


    public function __construct(SyslogBusiness $syslog,
                                EntityManagerInterface $doctrine,
                                IntegradorECommerceFactory $integradorECommerceFactory)
    {
        $this->syslog = $syslog->setApp('radx')->setComponent(self::class);
        $this->doctrine = $doctrine;
        $this->integradorBusinessFactory = $integradorECommerceFactory;
    }

    /**
     *
     * @param IntegrarProdutoEcommerceMessage $message
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function __invoke(IntegrarProdutoEcommerceMessage $message)
    {
        $this->syslog->info('queue: consumindo IntegrarProdutoEcommerceMessage (produto.id = ' . $message->produtoId . ')');
        $produto = $this->doctrine->getRepository(Produto::class)->find($message->produtoId);
        $integrador = $this->integradorBusinessFactory->getIntegrador();
        $integrador->integraProduto($produto, true);
        sleep(15);
    }
}