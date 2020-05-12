<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Vendas;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Vendas\PlanoPagtoRepository")
 * @ORM\Table(name="ven_plano_pagto")
 *
 * @author Carlos Eduardo Pauluk
 */
class PlanoPagto implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="codigo", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $codigo = null;

    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $descricao = null;

    /**
     *
     * @ORM\Column(name="ativo", type="boolean")
     * @Groups("entity")
     *
     * @var bool|null
     */
    public ?bool $ativo = true;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;


}