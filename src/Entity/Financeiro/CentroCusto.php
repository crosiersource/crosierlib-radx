<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade 'Centro de Custo'.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CentroCustoRepository")
 * @ORM\Table(name="fin_centrocusto")
 *
 * @author Carlos Eduardo Pauluk
 */
class CentroCusto implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="codigo", type="integer", nullable=false)
     * @Groups("entity")
     */
    public ?int $codigo = null;

    /**
     * @ORM\Column(name="descricao", type="string", nullable=false, length=40)
     * @Groups("entity")
     */
    public ?string $descricao;


    /**
     * @Groups("entity")
     * @return string
     */
    public function getDescricaoMontada(): string
    {
        return str_pad($this->codigo, 2, '0', STR_PAD_LEFT) . ' - ' . $this->descricao;
    }

}
