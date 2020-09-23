<?php


namespace CrosierSource\CrosierLibRadxBundle\Messenger\ECommerce\Message;


/**
 * Class IntegrarEstoqueEPrecosEcommerceMessage
 * @package App\Messenger\Message
 */
class IntegrarEstoqueEPrecosEcommerceMessage
{

    public array $produtosIds = [];

    public function __construct(array $produtosIds)
    {
        $this->produtosIds = $produtosIds;
    }


}