<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\ECommerce;


use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use Doctrine\DBAL\ConnectionException;

/**
 * @author Carlos Eduardo Pauluk
 */
interface IntegradorECommerce
{

    /**
     * @param \DateTime $dtVenda
     * @param bool|null $resalvar
     * @return int
     * @throws ViewException
     * @throws ConnectionException
     */
    public function obterVendas(\DateTime $dtVenda, ?bool $resalvar = false): int;

    /**
     * @param \DateTime $dtVenda
     */
    public function obterVendasPorData(\DateTime $dtVenda);

    /**
     * @param $idClienteECommerce
     */
    public function obterCliente($idClienteECommerce);

    /**
     * @param Venda $venda
     */
    public function reintegrarVendaParaCrosier(Venda $venda);

    /**
     * @param Venda $venda
     * @return \SimpleXMLElement|null
     */
    public function integrarVendaParaECommerce(Venda $venda);

}
