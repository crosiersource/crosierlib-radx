<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 *  @ApiResource(
 *     normalizationContext={"groups"={"entity","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"entity"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/produtoImagem/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/produtoImagem/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/produtoImagem/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/produtoImagem", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/produtoImagem", "security"="is_granted('ROLE_ESTOQUE')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "documento": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoImagemEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoImagemRepository")
 * @ORM\Table(name="est_produto_imagem")
 * @Vich\Uploadable
 *
 * @author Carlos Eduardo Pauluk
 */
class ProdutoImagem implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto")
     * @ORM\JoinColumn(name="produto_id", nullable=false)
     *
     * @var null|Produto
     */
    private $produto;

    /**
     * @Vich\UploadableField(mapping="produto_imagem", fileNameProperty="imageName")
     * @var null|File
     */
    private $imageFile;

    /**
     * @ORM\Column(name="image_name", type="string")
     * @Groups("entity")
     * @NotUppercase()
     * @var null|string
     */
    private $imageName;

    /**
     *
     * @ORM\Column(name="ordem", type="integer", nullable=true)
     * @Groups("entity")
     * @var null|integer
     */
    private $ordem;

    /**
     *
     * @ORM\Column(name="descricao", type="string", nullable=false)
     * @NotUppercase()
     * @Groups("entity")
     * @var null|string
     */
    private $descricao;

    /**
     * @return Produto|null
     */
    public function getProduto(): ?Produto
    {
        return $this->produto;
    }

    /**
     * @param Produto|null $produto
     * @return ProdutoImagem
     */
    public function setProduto(?Produto $produto): ProdutoImagem
    {
        $this->produto = $produto;
        return $this;
    }

    /**
     * @return File|null
     */
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     * @return ProdutoImagem
     * @throws \Exception
     */
    public function setImageFile(?File $imageFile = null): ProdutoImagem
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updated = new \DateTime();
        }
        return $this;
    }

    /**
     * @return null|string
     */
    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    /**
     * @param null|string $imageName
     * @return ProdutoImagem
     */
    public function setImageName(?string $imageName): ProdutoImagem
    {
        $this->imageName = $imageName;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrdem(): ?int
    {
        return $this->ordem;
    }

    /**
     * @param int|null $ordem
     * @return ProdutoImagem
     */
    public function setOrdem(?int $ordem): ProdutoImagem
    {
        $this->ordem = $ordem;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    /**
     * @param string|null $descricao
     * @return ProdutoImagem
     */
    public function setDescricao(?string $descricao): ProdutoImagem
    {
        $this->descricao = $descricao;
        return $this;
    }


}