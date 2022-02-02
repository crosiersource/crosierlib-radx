<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Ecommerce;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidade 'MercadoLivreItem'.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"mercadoLivreItem", "entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"mercadoLivreItem"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/ecommerce/mercadoLivreItem/{id}", "security"="is_granted('ROLE_ECOMM_ADMIN')"},
 *          "put"={"path"="/ecommerce/mercadoLivreItem/{id}", "security"="is_granted('ROLE_ECOMM_ADMIN')"},
 *          "delete"={"path"="/ecommerce/mercadoLivreItem/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/ecommerce/mercadoLivreItem", "security"="is_granted('ROLE_ECOMM_ADMIN')"},
 *          "post"={"path"="/ecommerce/mercadoLivreItem", "security"="is_granted('ROLE_ECOMM_ADMIN')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 * @ApiFilter(PropertyFilter::class)
 * @ApiFilter(BooleanFilter::class, properties={
 *     "atual": "exact",
 * })
 *
 * @ApiFilter(SearchFilter::class, properties={
 *     "clienteConfig": "exact",
 * })
 *
 * @ApiFilter(OrderFilter::class, properties={
 *     "id", 
 *     "updated", 
 *     "clienteConfig.cliente.nome",
 *     "statusTray",
 *     "dtVenda",
 *     "idTray",
 *     "pointSale",
 *     "valorTotal"
 * }, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Ecommerce\MercadoLivreItemEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Ecommerce\MercadoLivreItemRepository")
 * @ORM\Table(name="ecomm_ml_item")
 *
 * @author Carlos Eduardo Pauluk
 */
class MercadoLivreItem implements EntityId
{

    use EntityIdTrait;


    /**
     * @ORM\Column(name="uuid", type="string", nullable=false, length=36)
     * @NotUppercase()
     * @Groups("mercadoLivreItem")
     * @Assert\Length(min=36, max=36)
     *
     * @var string|null
     */
    public ?string $UUID = null;

    
    /**
     * 
     * 
     * @ORM\Column(name="mercadolivre_id", type="string", nullable=false)
     * @Groups("mercadoLivreItem")
     *
     * @var null|string
     */
    public ?string $mercadolivreId = null;


    /**
     * @ORM\Column(name="descricao", type="string", nullable=false)
     * @Groups("mercadoLivreItem")
     *
     * @var null|string
     */
    public ?string $descricao = null;

    
    /**
     *
     * @ORM\Column(name="preco_venda", type="decimal")
     * @Groups("mercadoLivreItem")
     *
     * @var null|float
     */
    public ?float $precoVenda = null;


    /**
     *
     * @ORM\ManyToOne(targetEntity="ClienteConfig")
     * @ORM\JoinColumn(name="cliente_config_id")
     * @Groups("mercadoLivreItem")
     *
     * @var null|ClienteConfig
     */
    public ?ClienteConfig $clienteConfig = null;


    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("mercadoLivreItem")
     */
    public ?array $jsonData = null;


}
