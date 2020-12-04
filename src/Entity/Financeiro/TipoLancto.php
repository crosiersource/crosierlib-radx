<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade Tipo de Lançamento.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\TipoLanctoRepository")
 * @ORM\Table(name="fin_tipo_lancto")
 *
 * @author Carlos Eduardo Pauluk
 */
class TipoLancto implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="codigo", type="integer", nullable=false)
     * @Groups("entity")
     *
     * @var int|null
     */
    public ?int $codigo = null;

    /**
     *
     * @ORM\Column(name="descricao", type="string", nullable=false, length=40)
     * @Groups("entity")
     *
     * @var string|null
     */
    public ?string $descricao = null;

    /**
     * Transient.
     *
     * @var string|null
     */
    public ?string $descricaoMontada = null;


    /**
     * @param bool|null $format
     * @return int|string|null
     */
    public function getCodigo(?bool $format = false)
    {
        if ($format) {
            return str_pad($this->codigo, 2, '0', STR_PAD_LEFT);
        }
        return $this->codigo;
    }

    /**
     * @Groups("entity")
     * @return null|string
     */
    public function getDescricaoMontada(): ?string
    {
        return $this->getCodigo(true) . ' - ' . $this->descricao;
    }


}
