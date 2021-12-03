<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidade para mensagens de retorno da Receita Federal.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\MsgRetornoRFRepository")
 * @ORM\Table(name="fis_msg_retorno_rf")
 *
 * @author Carlos Eduardo Pauluk
 */
class MsgRetornoRF implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="codigo", type="integer", nullable=false)
     * @var int|null
     */
    public ?int $codigo;

    /**
     * @ORM\Column(name="mensagem", type="string", nullable=false, length=2000)
     * @var string|null
     */
    public ?string $mensagem;

    /**
     * @ORM\Column(name="versao", type="string", nullable=false, length=10)
     * @var string|null
     */
    public ?string $versao;


    public function getCodigo(): ?int
    {
        return $this->codigo;
    }

    public function setCodigo($codigo): MsgRetornoRF
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getMensagem(): ?string
    {
        return $this->mensagem;
    }

    public function setMensagem($mensagem): MsgRetornoRF
    {
        $this->mensagem = $mensagem;
        return $this;
    }

    public function getVersao(): ?string
    {
        return $this->versao;
    }

    public function setVersao($versao): MsgRetornoRF
    {
        $this->versao = $versao;
        return $this;
    }


}