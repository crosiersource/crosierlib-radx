<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade 'ImportExtratoCabec'.
 *
 * Registra as relações de-para entre campos da fin_movimentacao e campos do CSV.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\ImportExtratoCabecRepository")
 * @ORM\Table(name="fin_import_extrato_cabec")
 *
 * @author Carlos Eduardo Pauluk
 */
class ImportExtratoCabec implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="tipo_extrato", type="string", nullable=false, length=100)
     * @Groups("entity")
     */
    public ?string $tipoExtrato = null;

    /**
     * @ORM\Column(name="campo_sistema", type="string", nullable=false, length=100)
     * @Groups("entity")
     */
    public ?string $campoSistema = null;

    /**
     * @ORM\Column(name="campos_cabecalho", type="string", nullable=false, length=200)
     * @Groups("entity")
     */
    public ?string $camposCabecalho = null;

    /**
     * @ORM\Column(name="formato", type="string", nullable=true, length=100)
     * @Groups("entity")
     */
    public ?string $formato = null;


}

