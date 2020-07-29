<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\Config\AppConfigEntityHandler;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\ImageUtils\ImageUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Grupo;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoImagem;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Subgrupo;
use CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoImagemRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * ProdutoEntityHandler constructor.
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param LoggerInterface $logger
     * @param AppConfigEntityHandler $appConfigEntityHandler
     * @param UploaderHelper $uploaderHelper
     */
    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness $syslog,
                                LoggerInterface $logger,
                                AppConfigEntityHandler $appConfigEntityHandler,
                                UploaderHelper $uploaderHelper)
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
        if (!$produto->UUID) {
            $produto->UUID = StringUtils::guidv4();
        }

        if (!$produto->depto) {
            $produto->depto = $this->doctrine->getRepository(Depto::class)->find(1);
        }
        if (!$produto->grupo) {
            $produto->grupo = $this->doctrine->getRepository(Grupo::class)->find(1);
        }
        if (!$produto->subgrupo) {
            $produto->subgrupo = $this->doctrine->getRepository(Subgrupo::class)->find(1);
        }

        if (!$produto->codigo) {
            $produto->codigo = StringUtils::guidv4();
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

        if ($produto->jsonData['qtde_imagens'] > 0) {

            // Se já tem registrado a imagem1...
            if ($produto->jsonData['imagem1'] ?? false) {
                $primeiraDasImagens_semExtensao = substr($imagens[0]->getImageName(), 0, strpos($imagens[0]->getImageName(), '.'));
                $imagem1_semExtensao = substr($produto->jsonData['imagem1'], 0, strpos($produto->jsonData['imagem1'], '.'));
                // Verifica se é a mesma da primeira imagem, porém já em thumbnail. Se não...
                if ($primeiraDasImagens_semExtensao . '_thumbnail' !== $imagem1_semExtensao) {
                    $imgName_thumbnail = $this->gerarThumbnail($produto, $imagens[0]->getImageName());
                    $produto->jsonData['imagem1'] = $imgName_thumbnail;
                }
            } else {
                $imgName_thumbnail = $this->gerarThumbnail($produto, $imagens[0]->getImageName());
                $produto->jsonData['imagem1'] = $imgName_thumbnail;
            }
        } else {
            unset($produto->jsonData['imagem1']);
        }


        if (!isset($produto->jsonData['ecommerce_id'])) {
            $produto->jsonData['ecommerce_id'] = 0;
        }

        $this->calcPorcentPreench($produto);
    }

    /**
     * @param Produto $produto
     * @param string|null $img
     * @return string
     */
    public function gerarThumbnail(Produto $produto, string $img = null)
    {
        $url = $_SERVER['CROSIERAPP_URL'] . '/images/produtos/' . $produto->depto->getId() . '/' . $produto->grupo->getId() . '/' . $produto->subgrupo->getId() . '/' . $img;

        $imgUtils = new ImageUtils();
        $imgUtils->load($url);

        $pathinfo = pathinfo($url);
        $parsedUrl = parse_url($url);

        $imgUtils->resizeToWidth(50);

        // '%kernel.project_dir%/public/images/produtos'
        $thumbnail = $_SERVER['DOCUMENT_ROOT'] .
            str_replace($pathinfo['basename'], '', $parsedUrl['path']) .
            $pathinfo['filename'] . '_thumbnail.' . $pathinfo['extension'];
        $imgUtils->save($thumbnail);

        return $pathinfo['filename'] . '_thumbnail.' . $pathinfo['extension'];
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
            if ($produto->getImagens() && $produto->getImagens()->count() >= $i) {
                $preench += $pesos['imagem'] ?? 1;
            } else {
                $camposFaltantes .= 'Imagem ' . $i . ' (1%)|';
            }
        }

        $totalPreench = $preench / $pesoTotal;

        $produto->jsonData['porcent_preench'] = $totalPreench;
        $produto->jsonData['porcent_preench_campos_faltantes'] = $camposFaltantes;

        $this->verificaPathDasImagens($produto);

    }

    /**
     * @param Produto $produto
     */
    private function verificaPathDasImagens(Produto $produto)
    {
        /** @var ProdutoImagem $imagem */
        foreach ($produto->imagens as $imagem) {
            $arquivo = $this->parameterBag->get('kernel.project_dir') . '/public' . $this->uploaderHelper->asset($imagem, 'imageFile');
            if (!file_exists($arquivo)) {
                /** @var Connection $conn */
                $conn = $this->getDoctrine()->getConnection();
                // Como ainda não foi salvo (estou no beforeSave), então ainda posso pegar os valores anteriores na base
                $rImagem = $conn->fetchAll('select i.id, i.produto_id, depto_id, grupo_id, subgrupo_id, image_name from est_produto p, est_produto_imagem i where p.id = i.produto_id AND i.id = :image_id', ['image_id' => $imagem->getId()]);

                $caminhoAntigo = $this->parameterBag->get('kernel.project_dir') .
                    '/public/images/produtos/' .
                    $rImagem[0]['depto_id'] . '/' .
                    $rImagem[0]['grupo_id'] . '/' .
                    $rImagem[0]['subgrupo_id'] . '/' . $rImagem[0]['image_name'];

                $somenteNovaPasta = str_replace(basename($arquivo), '', $arquivo);

                @mkdir($somenteNovaPasta, 0777, true);
                rename($caminhoAntigo, $arquivo);

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
                $qtdeFotosMinima = (int)$cfgQtdeFotosMinima->getValor();
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao pesquisar AppConfig para "qtdeFotosMinima"');
        }
        return $qtdeFotosMinima;
    }

}