<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\Config\AppConfigEntityHandler;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\ImageUtils\ImageUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Fornecedor;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Grupo;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoImagem;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoPreco;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Subgrupo;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade;
use CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoImagemRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @author Carlos Eduardo Pauluk
 */
class ProdutoEntityHandler extends EntityHandler
{

    private LoggerInterface $logger;

    private AppConfigEntityHandler $appConfigEntityHandler;

    private UploaderHelper $uploaderHelper;

    public bool $gerarThumbnailAoSalvar = true;


    public function __construct(ManagerRegistry        $doctrine,
                                Security               $security,
                                ParameterBagInterface  $parameterBag,
                                SyslogBusiness         $syslog,
                                LoggerInterface        $logger,
                                AppConfigEntityHandler $appConfigEntityHandler,
                                UploaderHelper         $uploaderHelper)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->logger = $logger;
        $this->appConfigEntityHandler = $appConfigEntityHandler;
        $this->uploaderHelper = $uploaderHelper;

    }

    public function getEntityClass(): string
    {
        return Produto::class;
    }

    public function beforeSave(/** @var Produto $produto */ $produto)
    {
        if (!$produto->status) {
            $produto->status = 'INATIVO';
        }
        if (!$produto->UUID) {
            $produto->UUID = StringUtils::guidv4();
        }

        if (!$produto->unidadePadrao) {
            // est_unidade.id = 1 deve ser 'UN'
            $produto->unidadePadrao = $this->doctrine->getRepository(Unidade::class)->find(1);
        }
        if (!$produto->depto) {
            // est_depto.id = 1 deve ser o 'INDEFINIDO'
            $produto->depto = $this->doctrine->getRepository(Depto::class)->find(1);
        }
        if (!$produto->grupo) {
            // est_grupo.id = 1 deve ser o 'INDEFINIDO'
            $produto->grupo = $this->doctrine->getRepository(Grupo::class)->find(1);
        }
        if (!$produto->subgrupo) {
            // est_subgrupo.id = 1 deve ser o 'INDEFINIDO'
            $produto->subgrupo = $this->doctrine->getRepository(Subgrupo::class)->find(1);
        }
        if (!$produto->fornecedor) {
            // est_fornecedor.id = 1 deve ser o 'INDEFINIDO'
            $produto->fornecedor = $this->doctrine->getRepository(Fornecedor::class)->find(1);
        }

        if (!$produto->codigo) {
            $rsProxCodigo = $this->doctrine->getConnection()->fetchAssociative('SELECT max(cast(codigo as unsigned))+1 as prox FROM est_produto WHERE codigo < 2147483647');
            $rsProxCodigo['prox'] = $rsProxCodigo['prox'] ?: 1;
            $produto->codigo = $rsProxCodigo['prox'];
        }

        $produto->jsonData['subgrupo_codigo'] = $produto->subgrupo->codigo;
        $produto->jsonData['subgrupo_nome'] = $produto->subgrupo->nome;

        $produto->jsonData['grupo_id'] = $produto->subgrupo->grupo->getId();
        $produto->jsonData['grupo_codigo'] = $produto->subgrupo->grupo->codigo;
        $produto->jsonData['grupo_nome'] = $produto->subgrupo->grupo->nome;

        $produto->jsonData['depto_id'] = $produto->subgrupo->grupo->depto->getId();
        $produto->jsonData['depto_codigo'] = $produto->subgrupo->grupo->depto->codigo;
        $produto->jsonData['depto_nome'] = $produto->subgrupo->grupo->depto->nome;

        $produto->jsonData['fornecedor_nome'] = $produto->fornecedor->nome;
        $produto->jsonData['fornecedor_nomeFantasia'] = $produto->fornecedor->nomeFantasia;


        /** @var ProdutoImagemRepository $repoProdutoImagem */
        $repoProdutoImagem = $this->getDoctrine()->getRepository(ProdutoImagem::class);
        $imagens = $repoProdutoImagem->findBy(['produto' => $produto], ['ordem' => 'ASC']);


        $produto->jsonData['qtde_imagens'] = count($imagens);

        if (!isset($produto->jsonData['ecommerce_id'])) {
            $produto->jsonData['ecommerce_id'] = 0;
        }

        $this->corrigirUnidadesPrecos($produto);

        $sqlPrecos = 'select lista.descricao as lista, u.label as unidade, preco.preco_prazo from est_produto_preco preco, est_unidade u, est_lista_preco lista where preco.produto_id = :produtoId and preco.lista_id = lista.id and preco.unidade_id = u.id and preco.atual IS TRUE';
        $rsPrecos = $this->getDoctrine()->getConnection()->fetchAllAssociative($sqlPrecos, ['produtoId' => $produto->getId()]);
        $produto->jsonData['info_precos'] = '';
        foreach ($rsPrecos as $rPreco) {
            $produto->jsonData['info_precos'] .= $rPreco['lista'] . ': R$ ' . number_format($rPreco['preco_prazo'], 2, ',', '.') . ' (' . $rPreco['unidade'] . ')<br>';
        }
        $produto->jsonData['info_precos'] = isset($produto->jsonData['info_precos']) ? substr($produto->jsonData['info_precos'], 0, -4) : '';

        $this->calcPorcentPreench($produto);

        $this->corrigirEstoqueProdutoComposicao($produto);

        $this->verificaPathDasImagens($produto);

        $this->gerarThumbnails($produto);
    }


    /**
     * @param Produto $produto
     */
    public function calcPorcentPreench(Produto $produto): void
    {
        $preench = 0;
        $camposFaltantes = '';

        $qtdeFotosMinima = $this->getQtdeFotosMinima();

        $pesoTotal = $qtdeFotosMinima;

        /** @var ProdutoRepository $repoProduto */
        $repoProduto = $this->doctrine->getRepository(Produto::class);
        $produtoJsonMetadata = $repoProduto->getJsonMetadata();
        if (!$produtoJsonMetadata) {
            return;
        }
        $jsonMetadata = json_decode($repoProduto->getJsonMetadata(), true);
        foreach ($jsonMetadata['campos'] as $nomeDoCampo => $metadata) {
            if (isset($metadata['soma_preench'])) {
                $pesoTotal += $metadata['soma_preench'];
                if ($produto->jsonData[$nomeDoCampo] ?? false) {
                    $preench += $metadata['soma_preench'];
                } else {
                    $camposFaltantes .= ($metadata['label'] ?? $nomeDoCampo) . ' (' . DecimalUtils::roundUp(bcdiv($metadata['soma_preench'] * 100, $pesoTotal, 2), 0) . '%)|';
                }
            }
        }

        for ($i = 1; $i <= $qtdeFotosMinima; $i++) {
            if ($produto->getImagens() && count($produto->getImagens()) >= $i) {
                $preench += $pesos['imagem'] ?? 1;
            } else {
                $camposFaltantes .= 'Imagem ' . $i . ' (1%)|';
            }
        }

        $totalPreench = $pesoTotal > 0 ? ($preench / $pesoTotal) : 0;

        $produto->jsonData['porcent_preench'] = $totalPreench;
        $produto->jsonData['porcent_preench_campos_faltantes'] = $camposFaltantes;

    }

    /**
     * @param Produto $produto
     */
    private function verificaPathDasImagens(Produto $produto)
    {
        $this->logger->info(
            'verificaPathDasImagens para ' .
            $produto->getId() . ' (' . $produto->nome . '). Total de imagens: ' . count($produto->getImagens()));
        /** @var ProdutoImagem $imagem */
        foreach ($produto->imagens as $imagem) {

            $arquivo = str_replace('/public/images/produtos', '', $_SERVER['PASTA_FOTOS_PRODUTOS']) . '/public' . $this->uploaderHelper->asset($imagem, 'imageFile');

            if (!file_exists($arquivo)) {
                /** @var Connection $conn */
                $conn = $this->getDoctrine()->getConnection();

                $this->logger->info('Arquivo ainda não existe: ' . $arquivo);

                // Como ainda não foi salvo (estou no beforeSave), então ainda posso pegar os valores anteriores na base
                $rImagem = $conn->fetchAllAssociative('select i.id, i.produto_id, depto_id, grupo_id, subgrupo_id, image_name from est_produto p, est_produto_imagem i where p.id = i.produto_id AND i.id = :image_id', ['image_id' => $imagem->getId()]);

                $caminhoAntigo = $_SERVER['PASTA_FOTOS_PRODUTOS'] . '/' .                   
                    $rImagem[0]['depto_id'] . '/' .
                    $rImagem[0]['grupo_id'] . '/' .
                    $rImagem[0]['subgrupo_id'] . '/' . $rImagem[0]['image_name'];


                if (file_exists($caminhoAntigo)) {
                    $this->logger->info('Caminho antigo já existente: ' . $caminhoAntigo);
                    // Se existe no caminhoAntigo, então é porque foi alterado o depto_id, grupo_id e/ou subgrupo_id
                    $somenteNovaPasta = str_replace(basename($arquivo), '', $arquivo);
                    $this->logger->info('Tentando criar (somente nova pasta): ' . $somenteNovaPasta);
                    @mkdir($somenteNovaPasta, 0777, true);
                    $this->logger->info('Renomeando/movendo do caminho antigo para o novo');
                    rename($caminhoAntigo, $arquivo);
                    $this->logger->info('OK');
                } else {
                    $this->logger->info('Arquivo (id = ' . $imagem->getId() . ') não existe no sistema de arquivos.');
                }
            } else {
                $this->logger->info('Arquivo JÁ existe: ' . $arquivo);
            }
        }
    }

    /**
     * @return int
     */
    private function getQtdeFotosMinima(): int
    {
        $qtdeFotosMinima = 0;
        try {
            /** @var AppConfigRepository $repoAppConfig */
            $repoAppConfig = $this->doctrine->getRepository(AppConfig::class);
            /** @var AppConfig $cfgQtdeFotosMinima */
            $cfgQtdeFotosMinima = $repoAppConfig->findOneBy(['appUUID' => $_SERVER['CROSIERAPP_UUID'], 'chave' => 'qtdeFotosMinima']);
            if ($cfgQtdeFotosMinima) {
                $qtdeFotosMinima = (int)$cfgQtdeFotosMinima->valor;
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao pesquisar AppConfig para "qtdeFotosMinima"');
        }
        return $qtdeFotosMinima;
    }

    /**
     * Corrige o preco_tabela e o qtde_estoque_total para o produto
     * @param Produto $produto
     */
    public function corrigirEstoqueProdutoComposicao(Produto $produto): void
    {
        $valorTotal = 0.0;
        $menorQtdeDisponivel = null;
        if ($produto->composicao === 'S') {

            foreach ($produto->composicoes as $itemComposicao) {

                $itemComposicao->qtdeEmEstoque = $itemComposicao->produtoFilho->jsonData['qtde_estoque_total'] ?? 0.0; // save
                $valorTotal = bcadd($valorTotal, $itemComposicao->getTotalComposicao(), 2);

                $qtdeDisponivel = $itemComposicao->qtdeEmEstoque >= $itemComposicao->qtde ? bcdiv($itemComposicao->qtdeEmEstoque, $itemComposicao->qtde, 0) : 0;
                $menorQtdeDisponivel = ($menorQtdeDisponivel !== null && $menorQtdeDisponivel < $qtdeDisponivel) ? $menorQtdeDisponivel : $qtdeDisponivel;

            }
            // dinâmicos...
            $produto->jsonData['preco_tabela'] = $valorTotal;
            $produto->jsonData['preco_site'] = $valorTotal;
            $produto->jsonData['qtde_estoque_total'] = $menorQtdeDisponivel;
        }
    }

    public function beforeClone(/** @var Produto $clone */ $clone)
    {
        $clone->nome .= ' (CLONADO)';
        $clone->UUID = null;
        $clone->codigo = null;
// FIXME: isto deve ser configurado no json_metadata
        if ($clone->jsonData['ecommerce_id'] ?? false) {
            $clone->jsonData['ecommerce_id'] = null;
        }
        if ($clone->jsonData['ecommerce_item_venda_id'] ?? false) {
            $clone->jsonData['ecommerce_item_venda_id'] = null;
        }
    }

    /**
     * @param $clone
     * @param $old
     * @throws ViewException
     */
    public function afterClone(/** @var Produto $clone */ $clone, /** @var Produto $old */ $old)
    {
        try {
            $conn = $this->getDoctrine()->getConnection();
            /** @var ProdutoPreco $oldPreco */
            foreach ($old->precos as $oldPreco) {

                $preco['margem'] = $oldPreco->margem;
                $preco['preco_custo'] = $oldPreco->precoCusto;
                $preco['preco_vista'] = $oldPreco->precoVista;
                $preco['preco_prazo'] = $oldPreco->precoPrazo;
                $preco['preco_promo'] = $oldPreco->precoPromo;
                $preco['custo_operacional'] = $oldPreco->custoOperacional;
                $preco['custo_financeiro'] = $oldPreco->custoFinanceiro;
                $preco['prazo'] = $oldPreco->prazo;
                $preco['unidade_id'] = $oldPreco->unidade->getId();
                $preco['produto_id'] = $clone->getId();
                $preco['lista_id'] = $oldPreco->lista->getId();
                $preco['atual'] = $oldPreco->atual ? 1 : 0;
                $preco['json_data'] = json_encode($oldPreco->jsonData);
                $preco['coeficiente'] = $oldPreco->coeficiente;
                $preco['dt_custo'] = $oldPreco->dtCusto->format('Y-m-d H:i:s');
                $preco['dt_preco_venda'] = $oldPreco->dtPrecoVenda->format('Y-m-d H:i:s');;
                $preco['inserted'] = (new \DateTime())->format('Y-m-d H:i:s');
                $preco['updated'] = (new \DateTime())->format('Y-m-d H:i:s');
                $preco['version'] = 0;
                $preco['user_inserted_id'] = 1;
                $preco['user_updated_id'] = 1;
                $preco['estabelecimento_id'] = 1;
                $conn->insert('est_produto_preco', $preco);
            }
        } catch (DBALException $e) {
            throw new ViewException('Erro ao clonar preços');
        }
    }


    /**
     * Se os preços cadastrados para o produto forem com apenas uma unidade, então seta todos para a unidade padrão.
     * @param Produto $produto
     * @throws ViewException
     */
    private function corrigirUnidadesPrecos(Produto $produto): void
    {
        try {
            $conn = $this->getDoctrine()->getConnection();
            $rsUnidades = $conn->fetchAllAssociative('SELECT distinct(unidade_id) as unidade_id FROM est_produto_preco WHERE produto_id = :produtoId', ['produtoId' => $produto->getId()]);
            if (count($rsUnidades) === 1) {
                if ((int)$rsUnidades[0]['unidade_id'] !== $produto->unidadePadrao->getId()) {
                    $conn->executeStatement('UPDATE est_produto_preco SET unidade_id = :unidadeId WHERE produto_id = :produtoId',
                        ['produtoId' => $produto->getId(), 'unidadeId' => $produto->unidadePadrao->getId()]);
                }
            }
        } catch (\Throwable $e) {
            throw new ViewException('Erro ao atualizar unidades dos preços do produto');
        }
    }

    private function gerarThumbnails(Produto $produto): void
    {
        $imagem1 = $produto->getImagens()[0] ?? null;
        if (!$imagem1) {
            $this->logger->debug('Produto sem imagens... sem geração de thumbnail');
            unset($produto->jsonData['imagem1']);
            return;
        }

        if ($this->gerarThumbnailAoSalvar) {
            // Se já tem registrado a imagem1...
            if ($produto->jsonData['imagem1'] ?? false) {
                $primeiraDasImagens_semExtensao =
                    substr(
                        $imagem1->getImageName(),
                        0,
                        strpos($imagem1->getImageName(), '.')
                    );
                $imagem1_semExtensao = substr($produto->jsonData['imagem1'], 0, strpos($produto->jsonData['imagem1'], '.'));
                // Verifica se é a mesma da primeira imagem, porém já em thumbnail. Se não...
                if ($primeiraDasImagens_semExtensao . '_thumbnail' !== $imagem1_semExtensao) {
                    $imgName_thumbnail = $this->gerarThumbnail($produto, $imagem1->getImageName());
                    $produto->jsonData['imagem1'] = $imgName_thumbnail;
                } else {
                    $this->logger->debug($primeiraDasImagens_semExtensao . ' com mais "_thumbnail" difere de "' . $imagem1_semExtensao . "'");
                }
            } else {
                $imgName_thumbnail = $this->gerarThumbnail($produto, $imagem1->getImageName());
                $produto->jsonData['imagem1'] = $imgName_thumbnail;
            }
        }
    }

    /**
     * @param Produto $produto
     * @param string|null $img
     * @return string
     */
    public function gerarThumbnail(Produto $produto, string $img = null)
    {
        $url = '';
        try {
            $url = $_SERVER['CROSIERAPPRADX_URL'] . '/images/produtos/' . $produto->depto->getId() . '/' . $produto->grupo->getId() . '/' . $produto->subgrupo->getId() . '/' . $img;
            $this->logger->debug('gerarThumbnail para "' . $url . '"');
            $imgUtils = new ImageUtils();
            $imgUtils->load($url);
            $pathinfo = pathinfo($url);
            $parsedUrl = parse_url($url);
            $imgUtils->resizeToWidth(50);// '%kernel.project_dir%/public/images/produtos'
            $thumbnail = str_replace('/public/images/produtos', '', $_SERVER['PASTA_FOTOS_PRODUTOS']) . '/public' .
                str_replace($pathinfo['basename'], '', $parsedUrl['path']) .
                $pathinfo['filename'] . '_thumbnail.' . $pathinfo['extension'];
            $this->logger->debug('thumbnail: "' . $thumbnail . '"');
            $imgUtils->save($thumbnail);
            return $pathinfo['filename'] . '_thumbnail.' . $pathinfo['extension'];
        } catch (\Exception $e) {
            $this->logger->error('Erro ao gerar thumbnail da imagem (' . $url . ')');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao gerar thumbnail da imagem (' . $url . ')', 0, $e);
        }
    }


}
