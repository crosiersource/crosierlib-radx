<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoImagem;
use CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoImagemRepository;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class ProdutoImagemEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return ProdutoImagem::class;
    }

    public function beforeSave(/** @var ProdutoImagem $produtoImagem */ $produtoImagem)
    {
        if (!$produtoImagem->getOrdem()) {
            /** @var ProdutoImagem $ultima */
            $o = $this->getDoctrine()->getConnection()->fetchAssociative('SELECT max(ordem) + 1 as ordem FROM est_produto_imagem WHERE produto_id = :produtoId', ['produtoId' => $produtoImagem->getProduto()->getId()]);
            $produtoImagem->setOrdem($o['ordem'] ?? 1);
        }
    }

    /**
     * @param array $ids
     * @return array
     * @throws ViewException
     */
    public function salvarOrdens(array $ids): array
    {
        /** @var ProdutoImagemRepository $repoImagem */
        $repoProdutoImagem = $this->getDoctrine()->getRepository(ProdutoImagem::class);
        $i = 1;
        $ordens = [];
        $imagens = $repoProdutoImagem->find($ids[0])->getProduto()->getImagens();
        /** @var ProdutoImagem $imagem */
        $varia = random_int(1, 1000000);
        foreach ($imagens as $imagem) {
            $imagem->setOrdem($imagem->getOrdem() + $varia + 1);
            $this->save($imagem);
        }
        foreach ($ids as $id) {
            if (!$id) continue;
            /** @var ProdutoImagem $produtoImagem */
            $produtoImagem = $repoProdutoImagem->find($id);
            $ordens[$id] = $i;
            $produtoImagem->setOrdem($i++);
            $this->save($produtoImagem);
        }
        return $ordens;
    }

    /**
     * @param Produto $produto
     * @throws ViewException
     */
    public function reordenar(Produto $produto)
    {
        $i = 1;
        foreach ($produto->getImagens() as $produtoImagem) {
            $produtoImagem->setOrdem($i++);
            $this->save($produtoImagem);
        }
    }

}