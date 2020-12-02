<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidade Modo de Movimentação.
 *
 * Informa se a movimentação foi em 'espécie', 'cheque', 'boleto', etc.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\ModoRepository")
 * @ORM\Table(name="fin_modo")
 *
 * @author Carlos Eduardo Pauluk
 */
class Modo implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="codigo", type="integer")
     * @Groups("entity")
     */
    public ?int $codigo = null;

    /**
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     */
    public ?string $descricao = null;

    /**
     * Informa se este modo é aceito para transferências próprias (entre
     * carteiras).
     *
     * @ORM\Column(name="transf_propria", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $modoDeTransfPropria = false;

    /**
     * Informa se este modo é aceito para transferências próprias (entre
     * carteiras).
     *
     * @ORM\Column(name="moviment_agrup", type="boolean")
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $modoDeMovimentAgrup = false;

    /**
     * Informa se este modo é aceito para transferências próprias (entre
     * carteiras).
     *
     * @ORM\Column(name="modo_cartao", type="boolean")
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $modoDeCartao = false;

    /**
     * Informa se este modo é aceito para transferências próprias (entre
     * carteiras).
     *
     * @ORM\Column(name="modo_cheque", type="boolean")
     * @Groups("entity")
     */
    public ?bool $modoDeCheque = false;

    /**
     * Informa se este modo é aceito para transferência/recolhimento de caixas.
     *
     * @ORM\Column(name="transf_caixa", type="boolean")
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $modoDeTransfCaixa = false;

    /**
     *
     * @ORM\Column(name="com_banco_origem", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("entity")
     *
     * @var bool|null
     */
    public ?bool $modoComBancoOrigem = false;


    /**
     * @param bool $format
     * @return int|null|string
     */
    public function getCodigo($format = false)
    {
        if ($format) {
            return str_pad($this->codigo, 2, "0", STR_PAD_LEFT);
        }
        return $this->codigo;

    }

    /**
     * @Groups("entity")
     * @return string
     */
    public function getDescricaoMontada(): string
    {
        return $this->getCodigo(true) . ' - ' . $this->descricao;
    }


}
