<?php


namespace CrosierSource\CrosierLibRadxBundle\Messenger\Ecommerce\Message;


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