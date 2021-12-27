<?php

namespace CrosierSource\CrosierLibRadxBundle\Messenger\ECommerce\Message;

/**
 * Mensagem utilizada pelo symfony/messenger que contém os dados de uma notificação enviada pelo ML.
 * 
 * @author Carlos Eduardo Pauluk
 */
class MlNotification
{

    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
    

}