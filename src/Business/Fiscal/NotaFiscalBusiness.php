<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Base\Municipio;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Entity\Security\User;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\Config\AppConfigEntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Base\MunicipioRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoComposicao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\FinalidadeNF;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\IndicadorFormaPagto;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NCM;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalCartaCorrecao;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalVenda;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\TipoNotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM\ClienteEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\MovimentacaoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalHistoricoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalItemEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalVendaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NCMRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Vendas\VendaRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\ResultSetMapping;
use NFePHP\Common\Exception\ValidatorException;
use NFePHP\DA\NFe\Danfe;
use Psr\Log\LoggerInterface;

/**
 *
 * @package App\Business\Fiscal
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalBusiness
{

    private Connection $conn;

    private LoggerInterface $logger;

    private SpedNFeBusiness $spedNFeBusiness;

    private AppConfigEntityHandler $appConfigEntityHandler;

    private NotaFiscalEntityHandler $notaFiscalEntityHandler;

    public NotaFiscalItemEntityHandler $notaFiscalItemEntityHandler;

    private NotaFiscalVendaEntityHandler $notaFiscalVendaEntityHandler;

    private NotaFiscalHistoricoEntityHandler $notaFiscalHistoricoEntityHandler;

    private MovimentacaoEntityHandler $movimentacaoEntityHandler;

    private NFeUtils $nfeUtils;

    private SyslogBusiness $syslog;

    private ClienteEntityHandler $clienteEntityHandler;

    private VendaEntityHandler $vendaEntityHandler;

    /**
     * Não podemos usar o doctrine->getRepository porque ele não injeta as depêndencias que estão com @ required lá
     * @var NotaFiscalRepository
     */
    private NotaFiscalRepository $repoNotaFiscal;

    public function __construct(Connection                       $conn,
                                LoggerInterface                  $logger,
                                SpedNFeBusiness                  $spedNFeBusiness,
                                AppConfigEntityHandler           $appConfigEntityHandler,
                                NotaFiscalEntityHandler          $notaFiscalEntityHandler,
                                NotaFiscalItemEntityHandler      $notaFiscalItemEntityHandler,
                                NotaFiscalVendaEntityHandler     $notaFiscalVendaEntityHandler,
                                NotaFiscalHistoricoEntityHandler $notaFiscalHistoricoEntityHandler,
                                MovimentacaoEntityHandler        $movimentacaoEntityHandler,
                                NFeUtils                         $nfeUtils,
                                SyslogBusiness                   $syslog,
                                NotaFiscalRepository             $repoNotaFiscal,
                                ClienteEntityHandler             $clienteEntityHandler,
                                VendaEntityHandler               $vendaEntityHandler)
    {
        $this->conn = $conn;
        $this->logger = $logger;
        $this->spedNFeBusiness = $spedNFeBusiness;
        $this->appConfigEntityHandler = $appConfigEntityHandler;
        $this->notaFiscalEntityHandler = $notaFiscalEntityHandler;
        $this->notaFiscalItemEntityHandler = $notaFiscalItemEntityHandler;
        $this->notaFiscalVendaEntityHandler = $notaFiscalVendaEntityHandler;
        $this->notaFiscalHistoricoEntityHandler = $notaFiscalHistoricoEntityHandler;
        $this->movimentacaoEntityHandler = $movimentacaoEntityHandler;
        $this->nfeUtils = $nfeUtils;
        $this->syslog = $syslog->setApp('radx')->setComponent(self::class);
        $this->repoNotaFiscal = $repoNotaFiscal;
        $this->clienteEntityHandler = $clienteEntityHandler;
        $this->vendaEntityHandler = $vendaEntityHandler;
    }


    /**
     * Verifica se está acessando o arquivo controle.txt para evitar trabalhar com diretório desmontado.
     * @return bool
     */
    public function checkAcessoPVs(): bool
    {
        $dir = $_SERVER['PASTAARQUIVOSEKTFISCAL'];
        $files = scandir($dir, SCANDIR_SORT_NONE);
        return in_array('controle.txt', $files, true);
    }

    /**
     * Transforma um ven_venda em um fis_nf.
     *
     * @param Venda $venda
     * @param NotaFiscal $notaFiscal
     * @param bool $alterouTipo
     * @return null|NotaFiscal
     * @throws ViewException
     */
    public function saveNotaFiscalVenda(Venda $venda, NotaFiscal $notaFiscal, bool $alterouTipo): ?NotaFiscal
    {
        try {
            $this->syslog->info('saveNotaFiscalVenda - Início');
            $this->syslog->info('Venda (id): ' . $venda->getId());
            $this->syslog->info('Venda (dtVenda): ' . $venda->dtVenda->format('d/m/Y H:i:s'));
            $this->syslog->info('Venda (valorTotal): ' . $venda->valorTotal);

            $conn = $this->notaFiscalEntityHandler->getDoctrine()->getConnection();

            $this->notaFiscalEntityHandler->getDoctrine()->beginTransaction();

            $jaExiste = $conn->fetchAllAssociative('SELECT * FROM fis_nf_venda WHERE venda_id = :vendaId', ['vendaId' => $venda->getId()]);

            if ($jaExiste) {
                /** @var NotaFiscalRepository $repoNotaFiscal */
                $repoNotaFiscal = $this->notaFiscalEntityHandler->getDoctrine()->getRepository(NotaFiscal::class);
                /** @var NotaFiscal $notaFiscal */
                $notaFiscal = $repoNotaFiscal->find($jaExiste[0]['nota_fiscal_id']);
                $novaNota = false;
            } else {
                $novaNota = true;
            }

            $nfeConfigs = $this->nfeUtils->getNFeConfigsEmUso();

            $rNcmPadrao = $conn->fetchAllAssociative('SELECT valor FROM cfg_app_config WHERE chave = \'ncm_padrao\'');
            $ncmPadrao = $rNcmPadrao[0]['valor'] ?? null;

            if ($notaFiscal->getId()) {
                $conn = $this->notaFiscalEntityHandler->getDoctrine()->getConnection();
                $conn->delete('fis_nf_item', ['nota_fiscal_id' => $notaFiscal->getId()]);
                $notaFiscal->deleteAllItens(); // remove as referências no ORM
            }

            $notaFiscal->entradaSaida = 'S';
            $ambiente = $nfeConfigs['tpAmb'] === 1 ? 'PROD' : 'HOM';
            $notaFiscal->ambiente = $ambiente;

            if ($notaFiscal->tipoNotaFiscal === 'NFE') {
                if ($venda->cliente) {

                    if (!$notaFiscal->documentoDestinatario) {
                        $notaFiscal->documentoDestinatario = $venda->cliente->documento;
                    }
                    if (!$notaFiscal->xNomeDestinatario) {
                        $notaFiscal->xNomeDestinatario = $venda->cliente->nome;
                    }
                    if (!$notaFiscal->foneDestinatario) {
                        $notaFiscal->foneDestinatario = $venda->cliente->jsonData['fone1'] ?? '(00) 00000-0000';
                    }
                    if (!$notaFiscal->emailDestinatario) {
                        $notaFiscal->emailDestinatario = $venda->cliente->jsonData['email'] ?? '';
                    }

                    if (!$notaFiscal->logradouroDestinatario) {
                        // Se a venda é do ecommerce, então utiliza os dados da entrega para o endereço
                        if ($venda->jsonData['ecommerce_entrega_logradouro'] ?? false) {
                            $endereco_faturamento['logradouro'] = $venda->jsonData['ecommerce_entrega_logradouro'];
                            $endereco_faturamento['numero'] = $venda->jsonData['ecommerce_entrega_numero'] ?? '';
                            $endereco_faturamento['complemento'] = $venda->jsonData['ecommerce_entrega_complemento'] ?? '';
                            $endereco_faturamento['bairro'] = $venda->jsonData['ecommerce_entrega_bairro'] ?? '';
                            $endereco_faturamento['cidade'] = $venda->jsonData['ecommerce_entrega_cidade'] ?? '';
                            $endereco_faturamento['estado'] = $venda->jsonData['ecommerce_entrega_uf'] ?? '';
                            $endereco_faturamento['cep'] = $venda->jsonData['ecommerce_entrega_cep'] ?? '';
                        } else {
                            // se não, pega o primeiro endereço que esteja marcado como "FATURAMENTO"
                            $endereco_faturamento = $venda->cliente->getEnderecoByTipo('FATURAMENTO');
                        }
                    } else {
                        $endereco_faturamento['logradouro'] = $notaFiscal->logradouroDestinatario;
                        $endereco_faturamento['numero'] = $notaFiscal->numeroDestinatario;
                        $endereco_faturamento['complemento'] = $notaFiscal->complementoDestinatario;
                        $endereco_faturamento['bairro'] = $notaFiscal->bairroDestinatario;
                        $endereco_faturamento['cidade'] = $notaFiscal->cidadeDestinatario;
                        $endereco_faturamento['estado'] = $notaFiscal->estadoDestinatario;
                        $endereco_faturamento['cep'] = $notaFiscal->cepDestinatario;
                    }

                    if (!$endereco_faturamento) {
                        throw new ViewException('NFe sem endereço de faturamento');
                    } else {
                        if (!($endereco_faturamento['estado'] ?? false)) {
                            throw new ViewException('NFe sem UF no endereço de faturamento');
                        }

                        // Primeiro já preenche com os dados já obtidos para, logo depois, fazer a consulta na receita (caso dê algum problema nela, já estará com o endereço preenchido)
                        $notaFiscal->logradouroDestinatario = $endereco_faturamento['logradouro'] ?? '';
                        $notaFiscal->numeroDestinatario = $endereco_faturamento['numero'] ?? '';
                        $notaFiscal->complementoDestinatario = ($endereco_faturamento['complemento'] ?? '');
                        $notaFiscal->bairroDestinatario = $endereco_faturamento['bairro'] ?? '';
                        $notaFiscal->cepDestinatario = $endereco_faturamento['cep'] ?? '';
                        $notaFiscal->cidadeDestinatario = $endereco_faturamento['cidade'] ?? '';
                        $notaFiscal->estadoDestinatario = $endereco_faturamento['estado'];


                        if (strlen($notaFiscal->documentoDestinatario) === 14 &&
                            (!($endereco_faturamento['logradouro'] ?? false) ||
                                !($endereco_faturamento['complemento'] ?? false) ||
                                !($endereco_faturamento['bairro'] ?? false) ||
                                !($endereco_faturamento['cep'] ?? false) ||
                                !($endereco_faturamento['cidade'] ?? false) ||
                                !($endereco_faturamento['estado'] ?? false))) {

                            $endereco_consultado = null;
                            try {
                                $endereco_consultado = $this->consultarCNPJ($notaFiscal->documentoDestinatario, $endereco_faturamento['estado']);
                            } catch (ViewException $e) {
                                $this->syslog->error('Erro ao consultarCNPJ para o CNPJ ' . $notaFiscal->documentoDestinatario . ' de ' . $endereco_faturamento['estado']);
                            }

                            if (!isset($endereco_consultado['dados'])) {
                                $this->syslog->info('Nenhum dado retornado para endereço consultado (venda = ' . $venda->getId() . ')');
                            } else {

                                if (!$notaFiscal->inscricaoEstadualDestinatario) {
                                    $ie = preg_replace("/[^0-9]/", "", $endereco_consultado['dados']['IE'] ?? '');
                                    $notaFiscal->inscricaoEstadualDestinatario = $ie;
                                }

                                $notaFiscal->logradouroDestinatario = StringUtils::getFirstNonEmpty($endereco_consultado['dados']['logradouro'] ?? '', $notaFiscal->logradouroDestinatario);
                                $notaFiscal->numeroDestinatario = StringUtils::getFirstNonEmpty($endereco_consultado['dados']['numero'] ?? '', $notaFiscal->numeroDestinatario);
                                $notaFiscal->complementoDestinatario = StringUtils::getFirstNonEmpty($endereco_consultado['dados']['complemento'] ?? '', $notaFiscal->complementoDestinatario);
                                $notaFiscal->bairroDestinatario = StringUtils::getFirstNonEmpty($endereco_consultado['dados']['bairro'] ?? '', $notaFiscal->bairroDestinatario);
                                $notaFiscal->cepDestinatario = StringUtils::getFirstNonEmpty($endereco_consultado['dados']['CEP'] ?? '', $notaFiscal->cepDestinatario);

                                if (($endereco_consultado['dados']['cidade'] ?? false) and
                                    (($endereco_consultado['dados']['cidade'])->__toString() !== 'INFORMACAO NAO DISPONIVEL')) {
                                    $notaFiscal->cidadeDestinatario = StringUtils::getFirstNonEmpty($endereco_consultado['dados']['cidade'] ?? '', $notaFiscal->cidadeDestinatario);
                                    $notaFiscal->estadoDestinatario = StringUtils::getFirstNonEmpty($endereco_consultado['dados']['UF'] ?? '', $notaFiscal->estadoDestinatario);
                                }
                            }
                        }
                    }
                } else {
                    throw new ViewException('NFe sem cliente');
                }
            }

            if ($notaFiscal->tipoNotaFiscal === 'NFCE' ||
                ($nfeConfigs['idDest_sempre1'] ?? false) || ($notaFiscal->estadoDestinatario === $nfeConfigs['siglaUF'])) {
                $dentro_ou_fora = 'dentro';
            } else {
                $dentro_ou_fora = 'fora';
            }

            $cfop_padrao_dentro_do_estado = $this->conn->fetchAllAssociative('SELECT valor FROM cfg_app_config WHERE chave = :chave', ['chave' => 'fiscal.cfop_padrao_dentro_do_estado']);
            $cfop_padrao_dentro_do_estado = $cfop_padrao_dentro_do_estado[0]['valor'] ?? '5102';
            $cfop_padrao_fora_do_estado = $this->conn->fetchAllAssociative('SELECT valor FROM cfg_app_config WHERE chave = :chave', ['chave' => 'fiscal.cfop_padrao_fora_do_estado']);
            $cfop_padrao_fora_do_estado = $cfop_padrao_fora_do_estado[0]['valor'] ?? '6102';

            $notaFiscal->documentoEmitente = $nfeConfigs['cnpj'];
            $notaFiscal->xNomeEmitente = $nfeConfigs['razaosocial'];
            $notaFiscal->inscricaoEstadualEmitente = $nfeConfigs['ie'];

            $notaFiscal->naturezaOperacao = 'VENDA';

            $dtEmissao = new \DateTime();
            $dtEmissao->modify(' - 4 minutes');
            $notaFiscal->dtEmissao = $dtEmissao;
            $notaFiscal->dtSaiEnt = $dtEmissao;

            $notaFiscal->finalidadeNf = FinalidadeNF::NORMAL['key'];

            if ($alterouTipo) {
                $notaFiscal->dtEmissao = null;
                $notaFiscal->numero = null;
                $notaFiscal->cnf = null;
                $notaFiscal->chaveAcesso = null;
            }

            $notaFiscal->transpModalidadeFrete = 'SEM_FRETE';

            // $notaFiscal->setTranspValorTotalFrete($venda->jsonData['ecommerce_entrega_frete_calculado'] ?? null);
            // $valoresFreteItens = DecimalUtils::gerarParcelas($notaFiscal->getTranspValorTotalFrete() ?? 0, $venda->itens->count());

            $notaFiscal->indicadorFormaPagto = IndicadorFormaPagto::VISTA['codigo'];

            $ordem = 1;

            $itensNaNota = [];
            $this->syslog->info('Transformando itens de composição para itens únicos na nota');
            /** @var VendaItem $vendaItem */
            foreach ($venda->itens as $vendaItem) {
                if ($vendaItem->produto && $vendaItem->produto->composicao === 'S') {
                    $this->syslog->info('Item de composição encontrado: ' . $vendaItem->descricao);
                    $qtdeItens = $vendaItem->produto->composicoes->count();
                    if ($qtdeItens < 1) {
                        throw new ViewException('Produto de composição mas sem nenhum item. Verifique!');
                    }
                    $descontoPorItemMock = 0.0;
                    if ($vendaItem->desconto) {
                        $this->syslog->info('Desconto encontrado no item de composição: ' . $vendaItem->desconto);
                        $descontoPorItemMock = bcdiv($vendaItem->desconto, $qtdeItens, 2);
                    }
                    $totalDescontoMock = 0.0;

                    // Verifica se foi vendido com o mesmo valor do sistema (pode acontecer de trocarem direto lá
                    // na webstorm ou no mercadolivre), aí precisa ajustar aqui
                    $precoTotalItensComposicao = 0.0;
                    foreach ($vendaItem->produto->composicoes as $produtoComposicao) {
                        $precoTotalItensComposicao = bcadd($precoTotalItensComposicao, $produtoComposicao->getTotalComposicao(), 2);
                    }

                    $fatorDiferencaValorVenda = bcdiv($vendaItem->precoVenda, $precoTotalItensComposicao, 10);

                    $somatorioPrecosVendaItensComposicao = 0.0;

                    /** @var ProdutoComposicao $produtoComposicao */
                    foreach ($vendaItem->produto->composicoes as $produtoComposicao) {
                        $mockItem = new VendaItem();
                        $mockItem->produto = $produtoComposicao->produtoFilho;
                        $mockItem->qtde = bcmul($vendaItem->qtde, $produtoComposicao->qtde, 3);
                        $mockItem->precoVenda = bcmul($produtoComposicao->precoComposicao, $fatorDiferencaValorVenda, 10);
                        $mockItem->precoVenda = DecimalUtils::roundUp($mockItem->precoVenda, 2);
                        $somatorioPrecosVendaItensComposicao = bcadd($somatorioPrecosVendaItensComposicao, bcmul($mockItem->precoVenda, $mockItem->qtde, 2), 2);
                        $mockItem->desconto = $descontoPorItemMock;
                        $this->syslog->info('Desconto dividido: ' . $descontoPorItemMock);
                        $totalDescontoMock = bcadd($totalDescontoMock, $descontoPorItemMock, 2);
                        $itensNaNota[] = $mockItem;
                    }

                    $mockItem = $itensNaNota[0];

                    // Se o somatório após o ajuste for maior, acrescenta a diferença no primeiro
                    if ($somatorioPrecosVendaItensComposicao > $vendaItem->precoVenda) {
                        $difSomatorioPrecosVendaItensComposicao = bcsub($somatorioPrecosVendaItensComposicao, $vendaItem->total, 2);
                        $mockItem->desconto += $difSomatorioPrecosVendaItensComposicao;
                    }

                    // Mesma coisa: caso o desconto dê diferente, ajusta o desconto no primeiro
                    if ($totalDescontoMock > $vendaItem->desconto) {
                        $mockItem->desconto = bcsub($mockItem->desconto, bcsub($totalDescontoMock, $vendaItem->desconto, 2), 2);
                    } elseif ($vendaItem->desconto > $totalDescontoMock) {
                        $mockItem->desconto = bcadd($mockItem->desconto, bcsub($vendaItem->desconto, $totalDescontoMock, 2), 2);
                    }
                } else {
                    $this->syslog->info('Item não é composição: ' . $vendaItem->descricao);
                    $itensNaNota[] = $vendaItem;
                }
            }

            /** @var NotaFiscal $notaFiscal */
            $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal, false);


            // Atenção, aqui tem que verificar a questão do arredondamento
            if ($venda->subtotal > 0.0) {
                $this->syslog->info('Calculando fator de desconto pela diferença entre subtotal e valor total');
                $fatorDesconto = 1 - round(bcdiv($venda->valorTotal, $venda->subtotal, 6), 6);
                $this->syslog->info('Fator de desconto: ' . $fatorDesconto);
            } else {
                $this->syslog->info('Sem fator de desconto pois subtotal é zero');
                $fatorDesconto = 1;
            }

            $somaDescontosItens = 0.0;

            // Vendas podem ter descontos globais, mas NFs não.
            // Se uma venda tem apenas um desconto global e não nos itens, então o desconto global é rateado entre todos
            $algumItemTemDesconto = false;
            /** @var VendaItem $vendaItem */
            foreach ($itensNaNota as $vendaItem) {
                if ($vendaItem->desconto > 0) {
                    $algumItemTemDesconto = true;
                    $this->syslog->info('Item tem desconto: ' . $vendaItem->descricao);
                    $this->syslog->info('Vendas podem ter descontos globais, mas NFs não. Se uma venda tem apenas um desconto global e não nos itens, então o desconto global é rateado entre todos');
                    break;
                }
            }

            $codigoPadraoDoProduto = $this->conn->fetchAssociative('SELECT valor FROM cfg_app_config WHERE chave = :chave', ['chave' => 'fiscal.item_codigo_padrao']);
            if ($codigoPadraoDoProduto['valor'] ?? false) {
                $codigoPadraoDoProduto = $codigoPadraoDoProduto['valor'];
            }

            /** @var VendaItem $vendaItem */
            foreach ($itensNaNota as $vendaItem) {
                $this->syslog->info('Processando item da venda: ' . $vendaItem->descricao);

                $nfItem = new NotaFiscalItem();
                $nfItem->notaFiscal = $notaFiscal;

                $ncm = $vendaItem->jsonData['ncm'] ?? $vendaItem->produto->jsonData['ncm'] ?? $ncmPadrao ?? '00000000';

                $nfItem->ncm = $ncm;

                // $nfItem->jsonData['valor_frete_item'] = $valoresFreteItens[$ordem - 1] ?? 0.00;

                $nfItem->ordem = $ordem++;

                $nfItem->qtde = $vendaItem->qtde;
                $nfItem->valorUnit = $vendaItem->precoVenda;
                $valorTotalItem = bcmul($vendaItem->qtde, $vendaItem->precoVenda, 2);
                $nfItem->valorTotal = $valorTotalItem;

                if (!$algumItemTemDesconto) {
                    $this->syslog->info('Calculando desconto no item com base no fator de desconto:');
                    $vDesconto = round(bcmul($valorTotalItem, $fatorDesconto, 4), 2);
                    $this->syslog->info('Desconto no item: ' . $vDesconto);
                } else {
                    $vDesconto = bcmul($vendaItem->desconto, 1, 2); // joga p/ string certo
                    $this->syslog->info('Item com desconto específico: ' . $vDesconto);
                }

                $nfItem->valorDesconto = $vDesconto;

                // Somando aqui pra verificar depois se o total dos descontos dos itens bate com o desconto global da nota.
                $somaDescontosItens += $vDesconto;

                $nfItem->subTotal = $valorTotalItem;


                $cfop = $vendaItem->produto->jsonData['cfop_' . $dentro_ou_fora] ?? ($dentro_ou_fora === 'dentro' ? $cfop_padrao_dentro_do_estado : $cfop_padrao_fora_do_estado);
                $nfItem->cfop = $cfop;


                if ($vendaItem->unidade && $vendaItem->unidade->getId()) {
                    $nfItem->unidade = $vendaItem->unidade->label;
                } else if ($vendaItem->unidadeproduto->jsonData['unidade_produto'] ?? null) {
                    $nfItem->unidade = $vendaItem->produto->jsonData['unidade_produto'];
                } else {
                    $nfItem->unidade = 'UN';
                }


                // Ordem de preferência para setar a descrição do item na nota
                $descricaoNoItem = trim($vendaItem->descricao ?? '');
                $produtoNome = trim($vendaItem->produto ? $vendaItem->produto->nome : '');
                $produtoNomeJson = trim($vendaItem->jsonData['produto']['descricao'] ?? '');
                $descricaoDoItemNaNota = $descricaoNoItem ?: $produtoNome ?: $produtoNomeJson;

                // Ordem de preferência para setar o código do item na nota
                $codigoDoItemNaNota = null;
                if ($codigoPadraoDoProduto) {
                    if (strpos($codigoPadraoDoProduto, 'produto.jsonData.') !== FALSE) {
                        $codigoDoItemNaNota = $vendaItem->produto->jsonData[str_replace('produto.jsonData.', '', $codigoPadraoDoProduto)] ?? null;
                    } elseif (strpos($codigoPadraoDoProduto, 'jsonData.') !== FALSE) {
                        $codigoDoItemNaNota = $vendaItem->jsonData[str_replace('jsonData.', '', $codigoPadraoDoProduto)] ?? null;
                    } elseif ($codigoPadraoDoProduto === 'codigo') {
                        $codigoDoItemNaNota = $vendaItem->produto->codigo ?? null;
                    }
                }
                if (!$codigoDoItemNaNota) {
                    if ($vendaItem->produto) {
                        $codigoDoItemNaNota = $vendaItem->produto->getId();
                    } else {
                        $codigoDoItemNaNota = $vendaItem->ordem;
                    }
                }

                $nfItem->codigo = $codigoDoItemNaNota;
                $nfItem->descricao = $descricaoDoItemNaNota;

                $csosn = null;
                if ($vendaItem->produto->jsonData['csosn'] ?? false) {
                    $csosn = $vendaItem->produto->jsonData['csosn'];
                } elseif ($nfeConfigs['CSOSN_padrao'] ?? false) {
                    $csosn = $nfeConfigs['CSOSN_padrao'];
                }
                $nfItem->csosn = $csosn;
                $nfItem->cst = $vendaItem->produto->jsonData['cst_icms'] ?? null;
                $nfItem->cest = $vendaItem->produto->jsonData['cest'] ?? null;

                if (isset($vendaItem->produto->jsonData['aliquota_icms']) && ($vendaItem->produto->jsonData['aliquota_icms'] > 0)) {
                    $nfItem->icmsAliquota = $vendaItem->produto->jsonData['aliquota_icms'];
                    $icmsValor = DecimalUtils::round(bcmul(bcdiv($vendaItem->produto->jsonData['aliquota_icms'], 100.0, 6), $nfItem->subTotal, 4));
                    $nfItem->icmsValor = $icmsValor;
                    $nfItem->icmsValorBc = $nfItem->subTotal;
                    $nfItem->icmsModBC = $vendaItem->produto->jsonData['modalidade_icms'] ?? null;
                }

                if ($vendaItem->produto->jsonData['pis'] ?? false) {
                    $nfItem->pisAliquota = $vendaItem->produto->jsonData['pis'];
                    $pisValor = DecimalUtils::round(bcmul(bcdiv($vendaItem->produto->jsonData['pis'], 100.0, 6), $nfItem->subTotal, 4));
                    $nfItem->pisValor = $pisValor;
                    $nfItem->pisValorBc = $nfItem->subTotal;
                }

                if ($vendaItem->produto->jsonData['cofins'] ?? false) {
                    $nfItem->cofinsAliquota = $vendaItem->produto->jsonData['cofins'];
                    $cofinsValor = DecimalUtils::round(bcmul(bcdiv($vendaItem->produto->jsonData['cofins'], 100.0, 6), $nfItem->subTotal, 4));
                    $nfItem->cofinsValor = $cofinsValor;
                    $nfItem->cofinsValorBc = $nfItem->subTotal;
                }

                $notaFiscal->addItem($nfItem);
                $this->syslog->info('Valor Unitário do item: ' . $nfItem->valorUnit);
                $this->syslog->info('Qtde: ' . $nfItem->qtde);
                $this->syslog->info('Subtotal do item: ' . $nfItem->subtotal);
                $this->syslog->info('Total do item: ' . $nfItem->valorTotal);
                $this->notaFiscalItemEntityHandler->save($nfItem, false);
            }

            $this->notaFiscalEntityHandler->calcularTotais($notaFiscal);
            $totalDescontos = bcsub($notaFiscal->subtotal, $notaFiscal->valorTotal, 2);

            if ((float)bcsub(abs($totalDescontos), abs($somaDescontosItens), 2) !== 0.0) {
                $diferenca = $totalDescontos - $somaDescontosItens;
                $valorDesconto = bcadd($notaFiscal->getItens()
                    ->get(0)
                    ->valorDesconto, $diferenca, 2);
                $notaFiscal->getItens()
                    ->get(0)
                    ->valorDesconto = $valorDesconto;
                $notaFiscal->getItens()
                    ->get(0)
                    ->calculaTotais();
            }

            /** @var NotaFiscal $notaFiscal */
            $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);

            $this->handleClienteNotaFiscalVenda($notaFiscal, $venda);

            if ($novaNota) {
                $notaFiscalVenda = new NotaFiscalVenda();
                $notaFiscalVenda->notaFiscal = $notaFiscal;
                $notaFiscalVenda->venda = $venda;
                $this->notaFiscalVendaEntityHandler->save($notaFiscalVenda);
            }

            $this->notaFiscalEntityHandler->getDoctrine()->commit();
            return $notaFiscal;
        } catch (\Exception $e) {
            $this->notaFiscalEntityHandler->getDoctrine()->rollback();
            if ($e instanceof ViewException) {
                throw $e;
            }
            $erro = 'Erro ao gerar registro da Nota Fiscal';
            throw new \RuntimeException($erro, null, $e);
        }
    }


    /**
     * Lida com os campos que são gerados programaticamente.
     *
     * @param $notaFiscal
     * @return bool
     * @throws ViewException
     */
    public function handleIdeFields(NotaFiscal $notaFiscal): bool
    {
        try {
            $mudou = false;

            $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->documentoEmitente);
            $ambiente = $nfeConfigs['tpAmb'] === 1 ? 'PROD' : 'HOM';

            if (!$notaFiscal->uuid) {
                $notaFiscal->uuid = md5(uniqid(mt_rand(), true));
                $mudou = true;
            }
            if (!$notaFiscal->cnf) {
                $cNF = random_int(10000000, 99999999);
                $notaFiscal->cnf = $cNF;
                $mudou = true;
            }
            if (!$notaFiscal->ambiente) {
                $notaFiscal->ambiente = $ambiente;
            }
            // Rejeição 539: Duplicidade de NF-e, com diferença na Chave de Acesso
            // Rejeição 266: 266 - SERIE UTILIZADA FORA DA FAIXA PERMITIDA NO WEB SERVICE (0-889).
            if (!$notaFiscal->numero || in_array($notaFiscal->cStat, [539, 266], true)) {
                $notaFiscal->ambiente = $ambiente;

                if (!$notaFiscal->tipoNotaFiscal) {
                    throw new \Exception('Impossível gerar número sem saber o tipo da nota fiscal.');
                }
                $chaveSerie = 'serie_' . $notaFiscal->tipoNotaFiscal . '_' . $ambiente;
                $serie = $nfeConfigs[$chaveSerie];
                if (!$serie) {
                    throw new ViewException('Série não encontrada para ' . $chaveSerie);
                }
                $notaFiscal->serie = $serie;

                $nnf = $this->findProxNumFiscal($notaFiscal->documentoEmitente, $ambiente, $notaFiscal->serie, $notaFiscal->tipoNotaFiscal);
                $notaFiscal->numero = $nnf;
                $mudou = true;
            }
            if (!$notaFiscal->dtEmissao) {
                $notaFiscal->dtEmissao = new \DateTime();
                $mudou = true;
            }
            if ($mudou || !$notaFiscal->chaveAcesso || !preg_match('/[0-9]{44}/', $notaFiscal->chaveAcesso)) {
                $notaFiscal->chaveAcesso = $this->buildChaveAcesso($notaFiscal);
                $mudou = true;
            }
            if ($mudou) {
                $this->notaFiscalEntityHandler->save($notaFiscal);
            }
            return $mudou;
        } catch (\Throwable $e) {
            $this->syslog->error('handleIdeFields');
            $this->syslog->error($e->getMessage());
            throw new ViewException('Erro ao gerar campos ide');
        }
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return string
     * @throws ViewException
     */
    public function buildChaveAcesso(NotaFiscal $notaFiscal)
    {
        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->documentoEmitente);
        $cUF = '41';

        $cnpj = $nfeConfigs['cnpj'];
        $ano = $notaFiscal->dtEmissao->format('y');
        $mes = $notaFiscal->dtEmissao->format('m');
        $mod = TipoNotaFiscal::get($notaFiscal->tipoNotaFiscal)['codigo'];
        $serie = $notaFiscal->serie;
        if (strlen($serie) > 3) {
            throw new ViewException('Série deve ter no máximo 3 dígitos');
        }
        $nNF = $notaFiscal->numero;
        $cNF = $notaFiscal->cnf;

        // Campo tpEmis
        // 1-Emissão Normal
        // 2-Contingência em Formulário de Segurança
        // 3-Contingência SCAN (desativado)
        // 4-Contingência EPEC
        // 5-Contingência em Formulário de Segurança FS-DA
        // 6-Contingência SVC-AN
        // 7-Contingência SVC-RS
        $tpEmis = 1;

        $chaveAcesso = NFeKeys::build($cUF, $ano, $mes, $cnpj, $mod, $serie, $nNF, $tpEmis, $cNF);
        return $chaveAcesso;
    }


    /**
     * Corrige os NCMs. Na verdade troca para um NCM genérico nos casos onde o NCM informado não exista na base.
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws \Exception
     */
    public function corrigirNCMs(NotaFiscal $notaFiscal): NotaFiscal
    {
        $this->notaFiscalEntityHandler->getDoctrine()->refresh($notaFiscal);
        if ($notaFiscal->getItens()) {
            foreach ($notaFiscal->getItens() as $item) {
                /** @var NCMRepository $repoNCM */
                $repoNCM = $this->notaFiscalEntityHandler->getDoctrine()->getRepository(NCM::class);
                $existe = $repoNCM->findByNCM($item->ncm);
                if (!$existe) {
                    $rNcmPadrao = $this->notaFiscalEntityHandler->getDoctrine()->getConnection()->fetchAllAssociative('SELECT valor FROM cfg_app_config WHERE chave = \'ncm_padrao\'');
                    $ncmPadrao = $rNcmPadrao[0]['valor'] ?? null;
                    $item->ncm = $ncmPadrao;
                }
            }
        }
        $this->notaFiscalEntityHandler->getDoctrine()->flush();
        return $notaFiscal;
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws \Exception
     */
    public function faturarNFe(NotaFiscal $notaFiscal, ?bool $gerarXML = true): NotaFiscal
    {
        // Verifica algumas regras antes de mandar faturar na receita.
        $this->checkNotaFiscal($notaFiscal);

        $this->spedNFeBusiness->addHistorico($notaFiscal, -1, 'INICIANDO FATURAMENTO');
        if ($notaFiscal->isPermiteFaturamento()) {
            try {
                if ($notaFiscal->nRec) {
                    $this->spedNFeBusiness->consultaRecibo($notaFiscal);
                    if ($notaFiscal->cStat === 502) {
                        $notaFiscal->chaveAcesso = null; // será regerada no handleIdeFields()
                    }
                }
                $this->handleIdeFields($notaFiscal);
                if ($gerarXML || !$notaFiscal->getXmlNota() || $notaFiscal->getXMLDecoded()->infNFE !== null) {
                    $notaFiscal = $this->spedNFeBusiness->gerarXML($notaFiscal);
                }
                $notaFiscal = $this->spedNFeBusiness->enviaNFe($notaFiscal);
                $this->spedNFeBusiness->addHistorico($notaFiscal, $notaFiscal->cStat ?: -1, 'XML enviado', $notaFiscal->getXmlNota());
                if ($notaFiscal) {
                    $this->spedNFeBusiness->addHistorico($notaFiscal, $notaFiscal->cStat ?: -1, $notaFiscal->xMotivo, 'FATURAMENTO PROCESSADO');
                } else {
                    $this->spedNFeBusiness->addHistorico($notaFiscal, -2, 'PROBLEMA AO FATURAR');
                }
            } catch (ViewException $e) {
                $this->spedNFeBusiness->addHistorico($notaFiscal, -2, $e->getMessage());
                throw $e;
            }

        } else {
            $this->spedNFeBusiness->addHistorico($notaFiscal, 0, 'NOTA FISCAL NÃO FATURÁVEL. STATUS = [' . $notaFiscal->cStat . ']');
        }

        return $notaFiscal;
    }

    /**
     *
     * @param NotaFiscal $notaFiscal
     * @throws \Exception
     */
    public function checkNotaFiscal(NotaFiscal $notaFiscal): void
    {
        if (!$notaFiscal->isNossaEmissao()) {
            return;
        }

        if (!$notaFiscal) {
            throw new \RuntimeException('Nota Fiscal null');
        }
        if ($notaFiscal->cidadeDestinatario) {

            /** @var MunicipioRepository $repoMunicipio */
            $repoMunicipio = $this->notaFiscalEntityHandler->getDoctrine()->getRepository(Municipio::class);

            /** @var Municipio $r */
            $r = $repoMunicipio->findOneByFiltersSimpl([
                ['municipioNome', 'EQ', $notaFiscal->cidadeDestinatario],
                ['ufSigla', 'EQ', $notaFiscal->estadoDestinatario]
            ]);
            
            if (!$r) {
                if (strpos($notaFiscal->cidadeDestinatario, '´') !== false) {
                    $cidadeDestinatario = str_replace('´', "'", $notaFiscal->cidadeDestinatario);
                    $r = $repoMunicipio->findOneByFiltersSimpl([
                        ['municipioNome', 'EQ', $cidadeDestinatario],
                        ['ufSigla', 'EQ', $notaFiscal->estadoDestinatario]
                    ]);
                } elseif (strpos($notaFiscal->cidadeDestinatario, '\'') !== false) {
                    $cidadeDestinatario = str_replace("'", '´', $notaFiscal->cidadeDestinatario);
                    $r = $repoMunicipio->findOneByFiltersSimpl([
                        ['municipioNome', 'EQ', $cidadeDestinatario],
                        ['ufSigla', 'EQ', $notaFiscal->estadoDestinatario]
                    ]);
                }
            }
            

            if (!$r) {
                throw new ViewException('Município inválido: [' . $notaFiscal->cidadeDestinatario . '-' . $notaFiscal->estadoDestinatario . ']');
            }
        } else {
            if ($notaFiscal->tipoNotaFiscal === 'NFE') {
                throw new ViewException('Município do destinatário n/d');
            }
        }

        if ($notaFiscal->dtEmissao > $notaFiscal->dtSaiEnt) {
            throw new ViewException('Dt Emissão maior que Dt Saída/Entrada. Não é possível faturar.');
        }
    }

    public function permiteFaturamento(NotaFiscal $notaFiscal): bool
    {
        if ($notaFiscal && !$notaFiscal->getId()) {
            $notaFiscal->jsonData['permiteFaturamento'] = false;
            $notaFiscal->jsonData['msgPermiteFaturamento'] = 'Não (Nota Fiscal ainda sem id)';
            return false;
        }
        if ($notaFiscal && $notaFiscal->getId() && in_array($notaFiscal->cStat, [-100, 100, 101, 204, 135], false)) {
            $notaFiscal->jsonData['permiteFaturamento'] = false;
            $notaFiscal->jsonData['msgPermiteFaturamento'] = 'Não (cStat em ' . $notaFiscal->cStat . ')';
            return false;
        }
        if ($notaFiscal->getItens() && $notaFiscal->getItens()->count() === 0) {
            $notaFiscal->jsonData['permiteFaturamento'] = false;
            $notaFiscal->jsonData['msgPermiteFaturamento'] = 'Não (sem itens)';
            return false;
        }

        try {
            $this->checkNotaFiscal($notaFiscal);
        } catch (\Exception $e) {
            if ($e instanceof ViewException) {
                $notaFiscal->jsonData['msgPermiteFaturamento'] = 'Não (' . $e->getMessage() . ')';
                throw new ViewException($e->getMessage());
            } else {
                $this->syslog->err('Erro ao checkNotaFiscal: ' . $e->getMessage(), $e->getTraceAsString());
                $notaFiscal->jsonData['msgPermiteFaturamento'] = 'Não (Erro ao checkNotaFiscal)';
            }
            $notaFiscal->jsonData['permiteFaturamento'] = false;
            return false;
        }
        $notaFiscal->jsonData['permiteFaturamento'] = true;
        $notaFiscal->jsonData['msgPermiteFaturamento'] = 'Sim';
        return true;
    }


    /**
     * @throws ViewException
     */
    public function cancelar(NotaFiscal $notaFiscal): NotaFiscal
    {
        try {
            $this->spedNFeBusiness->addHistorico($notaFiscal, -1, 'INICIANDO CANCELAMENTO');
            $notaFiscal = $this->checkChaveAcesso($notaFiscal);
            $notaFiscalR = $this->spedNFeBusiness->cancelar($notaFiscal);
            if ($notaFiscalR) {
                $notaFiscal = $notaFiscalR;
                $this->spedNFeBusiness->addHistorico($notaFiscal, $notaFiscal->cStat ?: -1, $notaFiscal->xMotivo, 'CANCELAMENTO PROCESSADO');
                $notaFiscal = $this->consultarStatus($notaFiscal);
            } else {
                $this->spedNFeBusiness->addHistorico($notaFiscal, -2, 'PROBLEMA AO CANCELAR');
            }
            return $notaFiscal;
        } catch (\Exception $e) {
            $this->spedNFeBusiness->addHistorico($notaFiscal, -2, 'PROBLEMA AO CANCELAR: [' . $e->getMessage() . ']');
            $msg = ExceptionUtils::treatException($e);
            if (!$msg && $e instanceof ValidatorException) {
                $msg = $e->getMessage();
            }
            $this->spedNFeBusiness->addHistorico($notaFiscal, -2, $e->getMessage());
            throw $e;
        }
    }


    /**
     * @throws ViewException
     */
    public function checkChaveAcesso(NotaFiscal $notaFiscal)
    {
        if (!$notaFiscal->chaveAcesso) {
            $notaFiscal->chaveAcesso = $this->buildChaveAcesso($notaFiscal);

            $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
            $this->notaFiscalEntityHandler->getDoctrine()->flush();
        }
        return $notaFiscal;
    }


    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws ViewException
     */
    public function consultarStatus(NotaFiscal $notaFiscal): NotaFiscal
    {
        $this->spedNFeBusiness->addHistorico($notaFiscal, -1, 'INICIANDO CONSULTA DE STATUS');
        try {
            $notaFiscal = $this->spedNFeBusiness->consultarStatus($notaFiscal);
            if ($notaFiscal) {
                $this->spedNFeBusiness->addHistorico($notaFiscal, $notaFiscal->cStat ?: -1, $notaFiscal->xMotivo, 'CONSULTA DE STATUS PROCESSADA');
            } else {
                $this->spedNFeBusiness->addHistorico($notaFiscal, -2, 'PROBLEMA AO CONSULTAR STATUS');
            }
        } catch (\Exception $e) {
            $this->spedNFeBusiness->addHistorico($notaFiscal, -2, 'PROBLEMA AO CONSULTAR STATUS: [' . $e->getMessage() . ']');
        }
        return $notaFiscal;
    }


    /**
     * @throws ViewException
     */
    public function cartaCorrecao(NotaFiscalCartaCorrecao $cartaCorrecao)
    {
        $this->spedNFeBusiness->addHistorico($cartaCorrecao->notaFiscal, -1, 'INICIANDO ENVIO DA CARTA DE CORREÇÃO');
        try {
            $cartaCorrecao = $this->spedNFeBusiness->cartaCorrecao($cartaCorrecao);
            if ($cartaCorrecao) {
                $this->spedNFeBusiness->addHistorico(
                    $cartaCorrecao->notaFiscal,
                    $cartaCorrecao->notaFiscal->cStat,
                    $cartaCorrecao->notaFiscal->xMotivo,
                    'ENVIO DA CARTA DE CORREÇÃO PROCESSADO');
                $this->consultarStatus($cartaCorrecao->notaFiscal);
            } else {
                $this->spedNFeBusiness->addHistorico($cartaCorrecao->notaFiscal, -2, 'PROBLEMA AO ENVIAR CARTA DE CORREÇÃO');
            }
        } catch (\Exception $e) {
            $this->spedNFeBusiness->addHistorico($cartaCorrecao->notaFiscal, -2, 'PROBLEMA AO ENVIAR CARTA DE CORREÇÃO: [' . $e->getMessage() . ']');
        }
        return $cartaCorrecao->notaFiscal;
    }


    /**
     * @throws ViewException
     */
    public function consultarCNPJ(string $cnpj, string $uf)
    {
        $r = [];
        $infCons = $this->spedNFeBusiness->consultarCNPJ($cnpj, $uf);
        $cstat = (int)((string)($infCons->cStat ?? '0'))[0];
        if ($cstat !== 1) {
            $r['xMotivo'] = $infCons->xMotivo;
        } else {
            $r['dados'] = [
                'CNPJ' => $infCons->infCad->CNPJ ?? '',
                'IE' => $infCons->infCad->IE ?? '',
                'razaoSocial' => $infCons->infCad->xNome ?? '',
                'CNAE' => $infCons->infCad->CNAE ?? '',
                'logradouro' => $infCons->infCad->ender->xLgr ?? '',
                'numero' => $infCons->infCad->ender->nro ?? '',
                'complemento' => $infCons->infCad->ender->xCpl ?? '',
                'bairro' => $infCons->infCad->ender->xBairro ?? '',
                'cidade' => $infCons->infCad->ender->xMun ?? '',
                'UF' => $infCons->infCad->UF ?? '',
                'CEP' => $infCons->infCad->ender->CEP ?? '',
            ];
        }
        return $r;
    }


    /**
     * @throws \Exception
     */
    public function criarZip($mesano)
    {
        $mesano = str_replace(' - ', '', $mesano);
        $zip = new \ZipArchive();

        $pastaUnimake = $_SERVER['FISCAL_UNIMAKE_PASTAROOT'];
        $pastaXMLs = $pastaUnimake . '/enviado/Autorizados/' . $mesano;
        $pastaF = $_SERVER['PASTA_F'];

        $pastaNFEs = $pastaF . '/NOTAS FISCAIS/NFE/' . $mesano;
        $pastaNFCEs = $pastaF . '/NOTAS FISCAIS/NFCE/' . $mesano;
        $pastaCARTACORRs = $pastaF . '/NOTAS FISCAIS/CARTACORR/' . $mesano;

        $zipname = $pastaUnimake . '/backup/' . $mesano . '.zip';

        if ($zip->open($zipname, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception("cannot open <$zipname>");
        }

        $this->criarZipDir($zip, $pastaXMLs, 'xmls');
        $this->criarZipDir($zip, $pastaCARTACORRs, 'cartacorr');
        $this->criarZipDir($zip, $pastaNFCEs, 'nfce');
        $this->criarZipDir($zip, $pastaNFEs, 'nfe');

        // Zip archive will be created only after closing object
        $zip->close();
        return file_get_contents($zipname);
    }


    private function criarZipDir(\ZipArchive $zip, $pasta, $nomePasta)
    {
        $xmls = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pasta), \RecursiveIteratorIterator::LEAVES_ONLY);
        $zip->addEmptyDir($nomePasta);
        foreach ($xmls as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real && relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($pasta) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $nomePasta . '/' . $relativePath);
            }
        }
    }

    /**
     * @throws ViewException
     */
    public function colarItem(NotaFiscal $notaFiscal, NotaFiscalItem $notaFiscalItem)
    {
        /** @var NotaFiscalItem $novoItem */
        $novoItem = clone $notaFiscalItem;
        $novoItem->setId(null);
        $novoItem->notaFiscal = $notaFiscal;
        $novoItem->codigo = '?????';
        $novoItem->ordem = null;
        $this->notaFiscalItemEntityHandler->save($novoItem);
    }


    /**
     * @return array obtido a partir das cfg_app_config de nfeConfigs_%
     */
    public function getEmitentes(): array
    {
        $nfeConfigs = $this->conn->fetchAllAssociative('SELECT * FROM cfg_app_config WHERE chave LIKE \'nfeConfigs\\_%\'');
        $emitentes = [];
        foreach ($nfeConfigs as $nfeConfig) {
            $dados = json_decode($nfeConfig['valor'], true);
            $emitentes[] = [
                'cnpj' => $dados['cnpj'],
                'razaosocial' => $dados['razaosocial'],
                'ie' => $dados['ie'],
                'logradouro' => $dados['enderEmit_xLgr'],
                'numero' => $dados['enderEmit_nro'],
                'bairro' => $dados['enderEmit_xBairro'],
                'cep' => $dados['enderEmit_cep'],
                'cidade' => $dados['enderEmit_xMun'] ?? '',
                'estado' => $dados['siglaUF'],
                'fone1' => $dados['fone1'] ?? '',
            ];
        }
        return $emitentes;
    }

    public function isCnpjEmitente(string $cnpj): bool
    {
        $emitentes = $this->getEmitentes();
        foreach ($emitentes as $emitente) {
            if ($emitente['cnpj'] === $cnpj) {
                return true;
            }
        }
        return false;
    }


    /**
     * @throws ViewException
     */
    public function getEmitenteFromNFeConfigsByCNPJ(string $cnpj): array
    {
        $emitentes = $this->getEmitentes();
        foreach ($emitentes as $emitente) {
            if ($emitente['cnpj'] === $cnpj) {
                return $emitente;
            }
        }
        throw new ViewException('CNPJ não encontrado nos emitentes');
    }


    /**
     * @param Venda $venda
     * @return null|NotaFiscalVenda
     * @throws ViewException
     */
    public function findNotaFiscalByVenda(Venda $venda): ?NotaFiscal
    {
        try {
            $nfeConfigs = $this->nfeUtils->getNFeConfigsEmUso();
            $ambiente = $nfeConfigs['tpAmb'] === 1 ? 'PROD' : 'HOM';
            $sql = 'SELECT nf.id FROM fis_nf_venda nfv, fis_nf nf WHERE nf.id = nfv.nota_fiscal_id AND nfv.venda_id = :venda_id AND nf.ambiente = :ambiente';
            $results = $this->conn->fetchAllAssociative($sql,
                [
                    'venda_id' => $venda->getId(),
                    'ambiente' => $ambiente
                ]);
            if (!$results) {
                return null;
            }
            if (count($results) > 1) {
                throw new ViewException('Mais de uma Nota Fiscal encontrada para [' . $venda->getId() . '] em fis_nf_venda');
            }
            /** @var NotaFiscalRepository $repoNotaFiscal */
            $repoNotaFiscal = $this->notaFiscalEntityHandler->getDoctrine()->getRepository(NotaFiscal::class);
            /** @var NotaFiscal $notaFiscal */
            $notaFiscal = $repoNotaFiscal->find($results[0]['id']);
            return $notaFiscal;
        } catch (\Throwable $e) {
            if ($e instanceof ViewException) {
                $msg = $e->getMessage();
            } else {
                $msg = 'Ocorreu um erro ao pesquisar a nota fiscal da venda';
            }
            throw new ViewException($msg);
        }
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return null|NotaFiscalVenda
     * @throws ViewException
     */
    public function findVendaByNotaFiscal(NotaFiscal $notaFiscal): ?Venda
    {
        try {
            $nfeConfigs = $this->nfeUtils->getNFeConfigsEmUso();
            $ambiente = $nfeConfigs['tpAmb'] === 1 ? 'PROD' : 'HOM';
            $sql = 'SELECT nfv.venda_id FROM fis_nf_venda nfv, fis_nf nf WHERE nf.id = nfv.nota_fiscal_id AND nfv.nota_fiscal_id = :notaFiscalId AND nf.ambiente = :ambiente';
            $results = $this->conn->fetchAllAssociative($sql,
                [
                    'notaFiscalId' => $notaFiscal->getId(),
                    'ambiente' => $ambiente
                ]);
            if (!$results) {
                return null;
            }
            if (count($results) > 1) {
                throw new \LogicException('Mais de uma Venda encontrada para [' . $notaFiscal->getId() . ']');
            }
            /** @var VendaRepository $repoVenda */
            $repoVenda = $this->notaFiscalEntityHandler->getDoctrine()->getRepository(Venda::class);
            /** @var Venda $venda */
            $venda = $repoVenda->find($results[0]['venda_id']);
            return $venda;
        } catch (\Throwable $e) {
            throw new ViewException('Ocorreu um erro ao pesquisar a venda da nota fiscal');
        }
    }


    /**
     * @param string $documentoEmitente
     * @param string $ambiente
     * @param string $serie
     * @param string $tipoNotaFiscal
     * @return int
     */
    public function findProxNumFiscal(string $documentoEmitente, string $ambiente, string $serie, string $tipoNotaFiscal)
    {
        try {
            $conn = $this->notaFiscalEntityHandler->getDoctrine()->getConnection();
            $conn->beginTransaction();

            // Ex.: sequenciaNumNF_HOM_NFE_40
            $chave = 'sequenciaNumNF_' . $ambiente . '_' . $tipoNotaFiscal . '_' . $serie;

            $rs = $this->selectAppConfigSequenciaNumNFForUpdate($chave);

            if (!$rs || !$rs[0]) {
                $appConfig = new AppConfig();
                $appConfig->appUUID = $_SERVER['CROSIERAPP_UUID'];
                $appConfig->chave = $chave;
                $appConfig->valor = 1;
                $this->appConfigEntityHandler->save($appConfig);
                $rs = $this->selectAppConfigSequenciaNumNFForUpdate($chave);
            }
            $prox = $rs[0]['valor'];
            $configId = $rs[0]['id'];

//            // Verificação se por algum motivo a numeração na fis_nf já não está pra frente...
//            $ultimoNaBase = null;
//            $sqlUltimoNumero = 'SELECT max(numero) as numero FROM fis_nf WHERE cstat in (100,101,135) AND documento_emitente = :documento_emitente AND ambiente = :ambiente AND serie = :serie AND tipo = :tipoNotaFiscal';
//
//            $rUltimoNumero = $conn->fetchAllAssociative($sqlUltimoNumero,
//                [
//                    'documento_emitente' => $documentoEmitente,
//                    'ambiente' => $ambiente,
//                    'serie' => $serie,
//                    'tipoNotaFiscal' => $tipoNotaFiscal
//                ]);
//            $ultimoNaBase = $rUltimoNumero[0]['numero'] ?? 0;
//            if ($ultimoNaBase && $ultimoNaBase !== $prox) {
//                $prox = $ultimoNaBase; // para não pular numeração a toa
//            }
            $prox++;

            $updateSql = 'UPDATE cfg_app_config SET valor = :valor WHERE id = :id';
            $conn->executeStatement($updateSql, ['valor' => $prox, 'id' => $configId]);
            $conn->commit();

            return $prox;
        } catch (\Exception $e) {
            $this->notaFiscalEntityHandler->getDoctrine()->rollback();
            $this->syslog->error($e);
            $this->syslog->error('Erro ao pesquisar próximo número de nota fiscal para [' . $ambiente . '] [' . $serie . '] [' . $tipoNotaFiscal . ']');
            throw new \RuntimeException('Erro ao pesquisar próximo número de nota fiscal para [' . $ambiente . '] [' . $serie . '] [' . $tipoNotaFiscal . ']');
        }
    }

    /**
     * @param string $chave
     * @return mixed
     */
    public function selectAppConfigSequenciaNumNFForUpdate(string $chave)
    {
        // FOR UPDATE para garantir que ninguém vai alterar este valor antes de terminar esta transação
        $sql = 'SELECT id, valor FROM cfg_app_config WHERE app_uuid = :app_uuid AND chave LIKE :chave FOR UPDATE';
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('valor', 'valor');
        $rsm->addScalarResult('id', 'id');
        $query = $this->notaFiscalEntityHandler->getDoctrine()->createNativeQuery($sql, $rsm);
        $query->setParameter('app_uuid', $_SERVER['CROSIERAPP_UUID']);
        $query->setParameter('chave', $chave);
        return $query->getResult();
    }

    /**
     * Gera uma fatura e suas movimentações a partir dos dados de uma nota fiscal.
     *
     * @param NotaFiscal $notaFiscal
     * @throws ViewException
     */
    public function gerarFatura(NotaFiscal $notaFiscal)
    {
        if ($notaFiscal->jsonData['fatura'] ?? false) {
            if ($notaFiscal->jsonData['fatura']['fatura_id'] ?? false) {
                throw new ViewException('Nota Fiscal com fatura já vinculada: ' . $notaFiscal->jsonData['fatura']['fatura_id']);
            }

            $conn = $this->movimentacaoEntityHandler->getDoctrine()->getConnection();
            $conn->beginTransaction();

            try {
                $fatura = [];
                $fatura['json_data']['notaFiscal_id'] = $notaFiscal->getId();
                $fatura['json_data']['notaFiscal_nFat'] = $notaFiscal->jsonData['fatura']['nFat'];
                $fatura['json_data'] = json_encode($fatura['json_data']);
                $fatura['dt_fatura'] = $notaFiscal->dtEmissao->format('Y-m-d');
                $fatura['quitada'] = 0;
                $fatura['fechada'] = 1;
                $fatura['transacional'] = 0;
                $fatura['inserted'] = (new \DateTime())->format('Y-m-d H:i:s');
                $fatura['updated'] = (new \DateTime())->format('Y-m-d H:i:s');
                /** @var User $user */
                $user = $this->nfeUtils->security->getUser();
                $fatura['user_inserted_id'] = $this->nfeUtils->security->getUser() ? $user->getId() : 1;
                $fatura['user_updated_id'] = $this->nfeUtils->security->getUser() ? $user->getId() : 1;
                $fatura['estabelecimento_id'] = 1;

                $conn->insert('fin_fatura', $fatura);
                $faturaId = $conn->lastInsertId();

                $doctrine = $this->movimentacaoEntityHandler->getDoctrine();

                $repoFatura = $doctrine->getRepository(Fatura::class);
                /** @var Fatura $fatura */
                $fatura = $repoFatura->find($faturaId);


                $repoTipoLancto = $doctrine->getRepository(TipoLancto::class);
                /** @var TipoLancto $tipoLancto */
                $tipoLancto = $repoTipoLancto->findOneBy(['codigo' => 20]);

                $repoModo = $doctrine->getRepository(Modo::class);
                /** @var Modo $modo_boleto */
                $modo_boleto = $repoModo->findOneBy(['codigo' => 6]);

                $repoCarteira = $doctrine->getRepository(Carteira::class);
                /** @var Carteira $carteira_indefinida */
                $carteira_indefinida = $repoCarteira->findOneBy(['codigo' => 99]);

                $repoCategoria = $doctrine->getRepository(Categoria::class);
                /** @var Categoria $categoria_CustosMercadoria */
                $categoria_CustosMercadoria = $repoCategoria->findOneBy(['codigo' => 202001]);

                $repoCentroCusto = $doctrine->getRepository(CentroCusto::class);
                /** @var CentroCusto $centroCusto */
                $centroCusto = $repoCentroCusto->findOneBy(['codigo' => 1]);

                $qtdeTotal = count($notaFiscal->jsonData['fatura']['duplicatas']);
                $i = 1;
                foreach ($notaFiscal->jsonData['fatura']['duplicatas'] as $duplicada) {

                    $movimentacao = new Movimentacao();

                    $movimentacao->fatura = ($fatura);
                    $movimentacao->cedente = StringUtils::mascararCnpjCpf($notaFiscal->documentoEmitente) . ' - ' .
                        $notaFiscal->xNomeEmitente;
                    $movimentacao->tipoLancto = ($tipoLancto);
                    $movimentacao->modo = ($modo_boleto);
                    $movimentacao->carteira = ($carteira_indefinida);
                    $movimentacao->categoria = ($categoria_CustosMercadoria);
                    $movimentacao->centroCusto = ($centroCusto);
                    $movimentacao->status = ('ABERTA');

                    $movimentacao->dtMoviment = ($notaFiscal->dtEmissao);
                    $movimentacao->dtVencto = (DateTimeUtils::parseDateStr($duplicada['dVenc']));
                    $movimentacao->valor = ($duplicada['vDup']);
                    $movimentacao->parcelamento = (true);
                    $movimentacao->cadeiaOrdem = ($i);
                    $movimentacao->cadeiaQtde = ($qtdeTotal);

                    $movimentacao->jsonData['notafiscal_id'] = $notaFiscal->getId();

                    $movimentacao->descricao = ('DUPLICATA ' . $duplicada['nDup'] . ' DE ' . $notaFiscal->xNomeEmitente . ' ' . StringUtils::strpad($i, 2) . '/' . StringUtils::strpad($qtdeTotal, 2));

                    $movimentacao->quitado = (false);
                    $this->movimentacaoEntityHandler->save($movimentacao);
                    $i++;
                }
                $notaFiscal->jsonData['fatura']['fatura_id'] = $faturaId;
                $this->notaFiscalEntityHandler->save($notaFiscal);
                $conn->commit();
            } catch (\Exception $e) {
                $msg = ExceptionUtils::treatException($e, 'Erro ao gerar fatura');
                $this->syslog->err($msg, $e->getTraceAsString());
                throw new ViewException($msg, 0, $e);
            }
        }
    }


    /**
     * A venda pode ser para cliente não identificado e posteriormente ser faturada para cliente identificado.
     * Corrige isto (e salva o cliente caso não exista).
     *
     * @param NotaFiscal $notaFiscal
     * @param Venda $venda
     * @throws ViewException
     * @throws \Doctrine\DBAL\Exception
     */
    public function handleClienteNotaFiscalVenda(NotaFiscal $notaFiscal, Venda $venda)
    {
        if (trim($venda->jsonData['cliente_nome'] ?? '') === '') {
            if ($notaFiscal->documentoDestinatario) {
                $rsClienteId = $this->conn->fetchAssociative('SELECT id FROM crm_cliente WHERE documento = :documento', ['documento' => $notaFiscal->documentoDestinatario]);
                if ($rsClienteId) {
                    $repoCliente = $this->clienteEntityHandler->getDoctrine()->getRepository(Cliente::class);
                    $cliente = $repoCliente->find($rsClienteId['id']);
                } else {
                    $cliente = new Cliente();
                    $cliente->documento = $notaFiscal->documentoDestinatario;
                    $cliente->nome = $notaFiscal->xNomeDestinatario;
                    $this->clienteEntityHandler->save($cliente);
                }
                if ($notaFiscal->tipoNotaFiscal === 'NFE') {
                    $endereco = [
                        'tipo' => 'FATURAMENTO',
                        'cep' => $notaFiscal->cepDestinatario,
                        'logradouro' => $notaFiscal->logradouroDestinatario,
                        'numero' => $notaFiscal->numeroDestinatario,
                        'bairro' => $notaFiscal->bairroDestinatario,
                        'cidade' => $notaFiscal->cidadeDestinatario,
                        'estado' => $notaFiscal->estadoDestinatario,
                    ];
                    $cliente->inserirNovoEndereco($endereco);
                    $this->clienteEntityHandler->save($cliente);
                }

                $venda->jsonData['cliente_nome'] = $cliente->nome;
                $venda->jsonData['cliente_documento'] = $cliente->documento;
                $this->vendaEntityHandler->save($venda);
            }
        }
    }


    public function gerarPDF(NotaFiscal $notaFiscal)
    {
        try {
            if ($notaFiscal->isNossaEmissao() && $notaFiscal->isPermiteFaturamento()) {
                $notaFiscal = $this->spedNFeBusiness->gerarXML($notaFiscal);
            }
            $xml = $notaFiscal->getXMLDecodedAsString();
            if (!$xml) {
                throw new \RuntimeException('XML não encontrado');
            }
            $danfe = new Danfe($xml);
            $danfe->debugMode(false);
            $danfe->creditsIntegratorFooter('EKT Plus');


            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );

            $logo = null;
            $nfeConfigsEmUso = null;
            if ($notaFiscal->documentoEmitente && in_array($notaFiscal->documentoEmitente, $this->nfeUtils->getNFeConfigsCNPJs(), true)) {
                $nfeConfigsEmUso = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->documentoEmitente);

                $response = file_get_contents($nfeConfigsEmUso['logo_fiscal'] ?: $_SERVER['CROSIER_LOGO'], false, stream_context_create($arrContextOptions));
                $logo = 'data://text/plain;base64,' . base64_encode($response);
            }

            $pdf = $danfe->render($logo);

            return $pdf;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Ocorreu um erro durante o processamento :' . $e->getMessage());
        }
    }

    public function verificarESetarVenda(NotaFiscal $notaFiscal): void
    {
        $temVenda = $this->conn->fetchAssociative(
            'SELECT * FROM fis_nf_venda WHERE nota_fiscal_id = :nota_fiscal_id',
            [
                'nota_fiscal_id' => $notaFiscal->getId()
            ]
        );
        if ($temVenda) {
            $notaFiscal->jsonData['venda_id'] = $temVenda['venda_id'];
        }
    }

}
