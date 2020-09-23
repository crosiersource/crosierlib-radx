<?php


namespace CrosierSource\CrosierLibRadxBundle\Messenger\ECommerce\Message;


/**
 * Class IntegrarProdutoEcommerceMessage
 * @package App\Messenger\Message
 */
class IntegrarProdutoEcommerceMessage
{

    public int $produtoId;

    public function __construct(int $produtoId)
    {
        $this->produtoId = $produtoId;
    }


}