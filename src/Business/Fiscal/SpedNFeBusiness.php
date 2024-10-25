<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Base\Municipio;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Base\MunicipioRepository;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\FinalidadeNF;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\ModalidadeFrete;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalCartaCorrecao;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalEvento;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\TipoNotaFiscal;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalCartaCorrecaoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalEventoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalRepository;
use Doctrine\ORM\EntityManagerInterface;
use NFePHP\Common\Exception\ValidatorException;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Classe que trata da integração com a RF via projeto nfephp-org
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class SpedNFeBusiness
{

    private EntityManagerInterface $doctrine;

    private NotaFiscalEntityHandler $notaFiscalEntityHandler;

    private NotaFiscalEventoEntityHandler $notaFiscalEventoEntityHandler;

    private NotaFiscalCartaCorrecaoEntityHandler $notaFiscalCartaCorrecaoEntityHandler;

    private LoggerInterface $logger;

    private NFeUtils $nfeUtils;

    private ParameterBagInterface $params;


    public function __construct(EntityManagerInterface               $doctrine,
                                NotaFiscalEntityHandler              $notaFiscalEntityHandler,
                                NotaFiscalEventoEntityHandler        $notaFiscalEventoEntityHandler,
                                NotaFiscalCartaCorrecaoEntityHandler $notaFiscalCartaCorrecaoEntityHandler,
                                LoggerInterface                      $logger,
                                NFeUtils                             $nfeUtils,
                                ParameterBagInterface                $params,
                                SyslogBusiness                       $syslog
    )
    {
        $this->doctrine = $doctrine;
        $this->notaFiscalEntityHandler = $notaFiscalEntityHandler;
        $this->notaFiscalEventoEntityHandler = $notaFiscalEventoEntityHandler;
        $this->notaFiscalCartaCorrecaoEntityHandler = $notaFiscalCartaCorrecaoEntityHandler;
        $this->logger = $logger;
        $this->nfeUtils = $nfeUtils;
        $this->params = $params;
        $this->syslog = $syslog->setApp('radx')->setComponent(self::class);
    }


    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws ViewException
     */
    public function gerarXML(NotaFiscal $notaFiscal): NotaFiscal
    {
        $this->syslog->info('Iniciando geração do XML para a NF ' . $notaFiscal->numero . ' (' . $notaFiscal->serie . ') do emitente ' . $notaFiscal->documentoEmitente);
        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->doctrine->getRepository(AppConfig::class);
        $layoutXMLpadrao = $repoAppConfig->findByChave('fiscal.layoutPadraoXML');
        if (!$layoutXMLpadrao) {
            // antes era configurado no arquivo
            $layoutXMLpadrao = file_get_contents($this->params->get('kernel.project_dir') . '/files/Fiscal/exemplos/exemplo-nfe.xml');
        }

        $nfe = simplexml_load_string($layoutXMLpadrao);
        if (!$nfe) {
            throw new \RuntimeException('Não foi possível obter o template XML da NFe');
        }

        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->documentoEmitente);


        if ($nfeConfigs['cUF'] ?? false) {
            $nfe->infNFe->ide->cUF = $nfeConfigs['cUF'];
        }
        if ($nfeConfigs['cMunFG'] ?? false) {
            $nfe->infNFe->ide->cMunFG = $nfeConfigs['cMunFG'];
        }
        if ($nfeConfigs['tpImp'] ?? false) {
            $nfe->infNFe->ide->tpImp = $nfeConfigs['tpImp'];
        }
        if ($nfeConfigs['tpEmis'] ?? false) {
            $nfe->infNFe->ide->tpEmis = $nfeConfigs['tpEmis'];
        }
        if ($nfeConfigs['indFinal'] ?? false) {
            $nfe->infNFe->ide->indFinal = $nfeConfigs['indFinal'];
        }
        if ($nfeConfigs['indPres'] ?? false) {
            $nfe->infNFe->ide->indPres = $nfeConfigs['indPres'];
        }


        $nfe->infNFe->ide->nNF = $notaFiscal->numero;

        $nfe->infNFe->ide->cNF = $notaFiscal->cnf;

        $nfe->infNFe->ide->mod = TipoNotaFiscal::get($notaFiscal->tipoNotaFiscal)['codigo'];
        $nfe->infNFe->ide->serie = $notaFiscal->serie;

        $tpEmis = 1;
        $nfe->infNFe->ide->tpEmis = $tpEmis;

        $nfe->infNFe['Id'] = 'NFe' . $notaFiscal->chaveAcesso;
        $nfe->infNFe->ide->cDV = NFeKeys::verifyingDigit(substr($notaFiscal->chaveAcesso, 0, -1));

        $nfe->infNFe->ide->natOp = $notaFiscal->naturezaOperacao;

        $nfe->infNFe->ide->dhEmi = $notaFiscal->dtEmissao->format('Y-m-d\TH:i:sP');

        $nfe->infNFe->ide->tpNF = $notaFiscal->entradaSaida === 'E' ? '0' : '1';

        $finNFe = FinalidadeNF::get($notaFiscal->finalidadeNf)['codigo'];
        $nfe->infNFe->ide->finNFe = $finNFe;

        // Devolução
        if (in_array($finNFe, [2, 4], true)) {
            if (!$notaFiscal->a03idNfReferenciada) {
                throw new \RuntimeException('Nota fiscal ' . ($finNFe === 4 ? 'de devolução' : 'complementar') . ' sem Id NF Referenciada.');
            }
            // else
            $nfe->infNFe->ide->NFref->refNFe = $notaFiscal->a03idNfReferenciada;
        }

        if ($notaFiscal->tipoNotaFiscal === 'NFE') {
            $nfe->infNFe->ide->dhSaiEnt = $notaFiscal->dtSaiEnt->format('Y-m-d\TH:i:sP');
        } else {
            unset($nfe->infNFe->ide->dhSaiEnt); // NFCE não possui
            $nfe->infNFe->ide->idDest = 1;
        }


        $nfe->infNFe->emit->CNPJ = $nfeConfigs['cnpj'];
        $nfe->infNFe->emit->xNome = $nfeConfigs['razaosocial'];
        $nfe->infNFe->emit->xFant = $nfeConfigs['razaosocial'];
        $nfe->infNFe->emit->IE = $nfeConfigs['ie'];
        $nfe->infNFe->emit->enderEmit->xLgr = $nfeConfigs['enderEmit_xLgr'];
        $nfe->infNFe->emit->enderEmit->nro = $nfeConfigs['enderEmit_nro'];
        if ($nfeConfigs['enderEmit_xCpl'] ?? false) {
            $nfe->infNFe->emit->enderEmit->xCpl = $nfeConfigs['enderEmit_xCpl'];
        }
        $nfe->infNFe->emit->enderEmit->xBairro = $nfeConfigs['enderEmit_xBairro'];
        $nfe->infNFe->emit->enderEmit->CEP = preg_replace('/\D/', '', $nfeConfigs['enderEmit_cep']);
        $nfe->infNFe->emit->enderEmit->fone = preg_replace('/\D/', '', $nfeConfigs['telefone']);

        if ($nfeConfigs['CRT'] ?? false) {
            $nfe->infNFe->emit->CRT = $nfeConfigs['CRT'];
        }

        // 1=Operação interna;
        // 2=Operação interestadual;
        // 3=Operação com exterior.
        if ($notaFiscal->documentoDestinatario) {

            if (strlen($notaFiscal->documentoDestinatario) === 14) {
                $nfe->infNFe->dest->CNPJ = preg_replace("/[^0-9]/", '', $notaFiscal->documentoDestinatario);
            } else {
                $nfe->infNFe->dest->CPF = preg_replace("/[^0-9]/", '', $notaFiscal->documentoDestinatario);
            }

            if ($notaFiscal->ambiente === 'HOM') {
                $nfe->infNFe->dest->xNome = 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
            } else {
                $nfe->infNFe->dest->xNome = trim($notaFiscal->xNomeDestinatario);
            }

            if ($notaFiscal->tipoNotaFiscal === 'NFE') {

                if ($notaFiscal->jsonData['idDest'] ?? false) {
                    $idDest = $notaFiscal->jsonData['idDest'];
                } else {
                    if (($notaFiscal->estadoDestinatario === $nfeConfigs['siglaUF']) || ($nfeConfigs['idDest_sempre1'] ?? false)) {
                        $idDest = 1;
                    } else {
                        $idDest = 2;
                    }
                }
                $nfe->infNFe->ide->idDest = $idDest; // $nfe->infNFe->ide->idDest = 2;

                $nfe->infNFe->dest->enderDest->xLgr = trim($notaFiscal->logradouroDestinatario);
                $nfe->infNFe->dest->enderDest->nro = trim($notaFiscal->numeroDestinatario);
                if ($notaFiscal->complementoDestinatario) {
                    $nfe->infNFe->dest->enderDest->xCpl = trim($notaFiscal->complementoDestinatario);
                }
                $nfe->infNFe->dest->enderDest->xBairro = trim($notaFiscal->bairroDestinatario);


                /** @var MunicipioRepository $repoMunicipio */
                $repoMunicipio = $this->doctrine->getRepository(Municipio::class);

                /** @var Municipio $r */
                $r = $repoMunicipio->findOneByFiltersSimpl([
                    ['municipioNome', 'EQ', $notaFiscal->cidadeDestinatario],
                    ['ufSigla', 'EQ', $notaFiscal->estadoDestinatario]
                ]);

                if (!$r ||
                    strtoupper(StringUtils::removerAcentos($r->municipioNome)) !== strtoupper(StringUtils::removerAcentos($notaFiscal->cidadeDestinatario))) {
                    throw new ViewException('Município inválido: [' . $notaFiscal->cidadeDestinatario . '-' . $notaFiscal->estadoDestinatario . ']');
                }

                $nfe->infNFe->dest->enderDest->cMun = $r->municipioCodigo;
                $nfe->infNFe->dest->enderDest->xMun = $r->municipioNome;
                $nfe->infNFe->dest->enderDest->UF = $r->ufSigla;


                $nfe->infNFe->dest->enderDest->CEP = preg_replace('/\D/', '', $notaFiscal->cepDestinatario);
                $nfe->infNFe->dest->enderDest->cPais = 1058;
                $nfe->infNFe->dest->enderDest->xPais = 'BRASIL';
                if (trim($notaFiscal->foneDestinatario)) {
                    $nfe->infNFe->dest->enderDest->fone = preg_replace('/\D/', '', $notaFiscal->foneDestinatario);
                }
            }


            // 1=Contribuinte ICMS (informar a IE do destinatário);
            // 2=Contribuinte isento de Inscrição no cadastro de Contribuintes do ICMS;
            // 9=Não Contribuinte, que pode ou não possuir Inscrição Estadual no Cadastro de Contribuintes do ICMS.
            // Nota 1: No caso de NFC-e informar indIEDest=9 e não informar a tag IE do destinatário;
            // Nota 2: No caso de operação com o Exterior informar indIEDest=9 e não informar a tag IE do destinatário;
            // Nota 3: No caso de Contribuinte Isento de Inscrição (indIEDest=2), não informar a tag IE do destinatário.

            if ($notaFiscal->tipoNotaFiscal === 'NFCE') {
                $nfe->infNFe->dest->indIEDest = 9;
                unset($nfe->infNFe->transp);
                unset($nfe->infNFe->dest->IE);
            } else {
                if (($notaFiscal->inscricaoEstadualDestinatario === 'ISENTO') || !$notaFiscal->inscricaoEstadualDestinatario) {
                    unset($nfe->infNFe->dest->IE);
                    // Rejeição 805: A SEFAZ do destinatário não permite Contribuinte Isento de Inscrição Estadual
                    if (in_array($notaFiscal->estadoDestinatario, ['AM', 'BA', 'CE', 'GO', 'MG', 'MS', 'MT', 'PA', 'PE', 'RN', 'SE', 'SP'])) {
                        $nfe->infNFe->dest->indIEDest = 9;
                        if (strlen($notaFiscal->documentoDestinatario) === 11) {
                            $nfe->infNFe->ide->indFinal = 1; // nesses casos, sendo CPF considera sempre como consumidor final
                        }
                    } else {
                        $nfe->infNFe->dest->indIEDest = 2;
                    }
                } else {
                    $nfe->infNFe->dest->indIEDest = 1;
                    if ($notaFiscal->inscricaoEstadualDestinatario) {
                        $nfe->infNFe->dest->IE = trim($notaFiscal->inscricaoEstadualDestinatario);
                    } else {
                        unset($nfe->infNFe->dest->IE);
                    }
                }
            }
        } else {
            unset($nfe->infNFe->dest);
        }

        // 0=Sem geração de DANFE;
        // 1=DANFE normal, Retrato;
        // 2=DANFE normal, Paisagem;
        // 3=DANFE Simplificado;
        // 4=DANFE NFC-e;
        // 5=DANFE NFC-e em mensagem eletrônica (o envio de mensagem eletrônica pode ser feita de forma simultânea com a impressão do DANFE; usar o tpImp=5 quando esta for a única forma de disponibilização do DANFE).

        if ($notaFiscal->tipoNotaFiscal === 'NFCE') {
            $nfe->infNFe->ide->tpImp = 4;
        } else {
            $nfe->infNFe->ide->tpImp = 1;
        }

        // 1=Produção
        // 2=Homologação
        $nfe->infNFe->ide->tpAmb = $notaFiscal->ambiente === 'PROD' ? 1 : 2;

        unset($nfe->infNFe->det);
        $i = 1;

        $total_bcICMS = 0;
        $total_vICMS = 0;

        $total_vPIS = 0;

        $total_vCOFINS = 0;

        $total_vICMSUFDest = 0.00;

        $qtdeItens = $notaFiscal->getItens()->count();
        $rateioFrete = null;
        if ((float)$notaFiscal->transpValorTotalFrete !== 0.00) {
            $rateioFrete = DecimalUtils::gerarParcelas($notaFiscal->transpValorTotalFrete, $qtdeItens);
        }

        /** @var NotaFiscalItem $nfItem */
        foreach ($notaFiscal->getItens() as $nfItem) {

            $this->syslog->info('Gerando item ' . $nfItem->ordem . ' (' . $nfItem->codigo . ' - ' . $nfItem->descricao . ')');

            $itemXML = $nfe->infNFe->addChild('det');
            $itemXML['nItem'] = $nfItem->ordem;
            $itemXML->prod->cProd = $nfItem->codigo;
            $itemXML->prod->cEAN = 'SEM GTIN';

            if ($notaFiscal->ambiente === 'HOM' && $i === 1) {
                $xProd = 'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
            } else {
                $xProd = $nfItem->descricao;
            }

            $itemXML->prod->xProd = $xProd;
            $itemXML->prod->NCM = $nfItem->ncm;
            if ($nfItem->cest) {
                $itemXML->prod->CEST = $nfItem->cest;
            }
            $itemXML->prod->CFOP = $nfItem->cfop;
            $itemXML->prod->uCom = $nfItem->unidade;
            $itemXML->prod->qCom = $nfItem->qtde;
            $itemXML->prod->vUnCom = $nfItem->valorUnit;
            $itemXML->prod->vProd = number_format($nfItem->valorTotal, 2, '.', '');
            $itemXML->prod->cEANTrib = 'SEM GTIN';
            $itemXML->prod->uTrib = $nfItem->unidade;
            $itemXML->prod->qTrib = $nfItem->qtde;
            $itemXML->prod->vUnTrib = number_format($nfItem->valorUnit, 2, '.', '');

            if ($rateioFrete) {
                $itemXML->prod->vFrete = number_format($rateioFrete[$i - 1], 2, '.', '');
            }

            $this->syslog->info('Verificando desconto do item (' . $nfItem->valorDesconto . ')');
            if (bccomp($nfItem->valorDesconto, 0.00, 2)) {
                $itemXML->prod->vDesc = number_format(abs($nfItem->valorDesconto), 2, '.', '');
                $this->syslog->info('Desconto no XML: ' . $itemXML->prod->vDesc);
            } else {
                $this->syslog->info('Item sem Desconto');
            }

            $itemXML->prod->indTot = '1';

            $this->handleImpostos($nfe, $nfItem, $itemXML);

            $total_bcICMS += $nfItem->icmsValorBc;
            $total_vICMS += $nfItem->icmsValor;

            $total_vPIS += $nfItem->pisValor;

            $total_vCOFINS += $nfItem->cofinsValor;

            $total_vICMSUFDest += (float)$itemXML->imposto->ICMSUFDest->vICMSUFDest ?? 0.0;


            $i++;
        }
        $nfe->infNFe->addChild('total');
        $nfe->infNFe->total->ICMSTot->vBC = number_format($total_bcICMS, 2, '.', '');
        $nfe->infNFe->total->ICMSTot->vICMS = number_format($total_vICMS, 2, '.', '');
        $nfe->infNFe->total->ICMSTot->vICMSDeson = '0.00';

        if ($nfe->infNFe->dest->indIEDest == 9 && $nfe->infNFe->ide->indFinal == 1 && $nfe->infNFe->ide->idDest == 2) {
            $nfe->infNFe->total->ICMSTot->vFCPUFDest = '0.00';
            $nfe->infNFe->total->ICMSTot->vICMSUFDest = number_format($total_vICMSUFDest, 2, '.', '');
        }

        $nfe->infNFe->total->ICMSTot->vFCP = '0.00';
        $nfe->infNFe->total->ICMSTot->vBCST = '0.00';
        $nfe->infNFe->total->ICMSTot->vST = '0.00';
        $nfe->infNFe->total->ICMSTot->vFCPST = '0.00';
        $nfe->infNFe->total->ICMSTot->vFCPSTRet = '0.00';
        $nfe->infNFe->total->ICMSTot->vProd = number_format($notaFiscal->subtotal, 2, '.', '');
        $nfe->infNFe->total->ICMSTot->vFrete = $notaFiscal->transpValorTotalFrete ?? '0.00';
        $nfe->infNFe->total->ICMSTot->vSeg = '0.00';
        // if (bccomp($notaFiscal->getTotalDescontos(), 0.00, 2)) {
        $nfe->infNFe->total->ICMSTot->vDesc = number_format(abs($notaFiscal->totalDescontos), 2, '.', '');
        // }
        $nfe->infNFe->total->ICMSTot->vII = '0.00';
        $nfe->infNFe->total->ICMSTot->vIPI = '0.00';
        $nfe->infNFe->total->ICMSTot->vIPIDevol = '0.00';
        $nfe->infNFe->total->ICMSTot->vPIS = number_format($total_vPIS, 2, '.', '');;
        $nfe->infNFe->total->ICMSTot->vCOFINS = number_format($total_vCOFINS, 2, '.', '');;
        $nfe->infNFe->total->ICMSTot->vOutro = '0.00';
        $totalNota = bcadd($notaFiscal->valorTotal, $notaFiscal->transpValorTotalFrete ?? '0.00', 2);
        $nfe->infNFe->total->ICMSTot->vNF = number_format($totalNota, 2, '.', '');
        $nfe->infNFe->total->ICMSTot->vTotTrib = '0.00';

        if ($notaFiscal->tipoNotaFiscal === 'NFCE') {
            $nfe->infNFe->transp->modFrete = 9;

        } else {
            $nfe->infNFe->transp->modFrete = ModalidadeFrete::get($notaFiscal->transpModalidadeFrete)['codigo'];

            if ($notaFiscal->transpDocumento) {

                $nfe->infNFe->transp->transporta->CNPJ = $notaFiscal->transpDocumento;
                $nfe->infNFe->transp->transporta->xNome = trim($notaFiscal->transpNome);
                if ($notaFiscal->transpInscricaoEstadual) {
                    $nfe->infNFe->transp->transporta->IE = trim($notaFiscal->transpInscricaoEstadual);
                }

                $nfe->infNFe->transp->transporta->xEnder = substr($notaFiscal->transpEndereco, 0, 60);

                if (!$notaFiscal->transpCidade || !$notaFiscal->transpEstado) {
                    throw new ViewException('Cidade/UF da transportadora n/d');
                }

                /** @var MunicipioRepository $repoMunicipio */
                $repoMunicipio = $this->doctrine->getRepository(Municipio::class);

                /** @var Municipio $r */
                $r = $repoMunicipio->findOneByFiltersSimpl([
                    ['municipioNome', 'EQ', $notaFiscal->transpCidade],
                    ['ufSigla', 'EQ', $notaFiscal->transpEstado]
                ]);

                if (!$r || strtoupper(StringUtils::removerAcentos($r->municipioNome)) !== strtoupper(StringUtils::removerAcentos($notaFiscal->transpCidade))) {
                    throw new ViewException('Município da transportadora inválido : [' . $notaFiscal->transpCidade . '-' . $notaFiscal->transpEstado . ']');
                }


                $nfe->infNFe->transp->transporta->xMun = $r->municipioNome;
                $nfe->infNFe->transp->transporta->UF = $r->ufSigla;
            }

            if ($notaFiscal->transpQtdeVolumes) {
                $nfe->infNFe->transp->vol->qVol = number_format($notaFiscal->transpQtdeVolumes, 0);
            }
            if ($notaFiscal->transpEspecieVolumes) {
                $nfe->infNFe->transp->vol->esp = $notaFiscal->transpEspecieVolumes;
            }
            if ($notaFiscal->transpMarcaVolumes) {
                $nfe->infNFe->transp->vol->marca = $notaFiscal->transpMarcaVolumes;
            }
            if ($notaFiscal->transpNumeracaoVolumes) {
                $nfe->infNFe->transp->vol->nVol = $notaFiscal->transpNumeracaoVolumes;
            }

            if ($notaFiscal->transpPesoLiquido) {
                $nfe->infNFe->transp->vol->pesoL = number_format($notaFiscal->transpPesoLiquido, 3, '.', '');
            }
            if ($notaFiscal->transpPesoBruto) {
                $nfe->infNFe->transp->vol->pesoB = number_format($notaFiscal->transpPesoBruto, 3, '.', '');
            }

        }

        if ($finNFe === 3 or $finNFe === 4) {
            $nfe->infNFe->pag->detPag->tPag = '90';
            $nfe->infNFe->pag->detPag->vPag = '0.00';
        } else {
            $nfe->infNFe->pag->detPag->tPag = '01';
            $nfe->infNFe->pag->detPag->vPag = number_format($notaFiscal->valorTotal, 2, '.', '');
        }


        if ($notaFiscal->infoCompl) {
            $infoCompl = preg_replace("/\r/", '', $notaFiscal->infoCompl);
            $infoCompl = preg_replace("/\n/", ';', $infoCompl);
            $nfe->infNFe->infAdic->infCpl = trim($infoCompl);
        }

        $nfe->infNFe->infRespTec->CNPJ = $nfeConfigs['cnpj'];
        $nfe->infNFe->infRespTec->xContato = $nfeConfigs['infRespTec_xContato'];
        $nfe->infNFe->infRespTec->email = $nfeConfigs['infRespTec_email'];
        $nfe->infNFe->infRespTec->fone = preg_replace('/\D/', '', $nfeConfigs['telefone']);


        // Número randômico para que não aconteça de pegar XML de retorno de tentativas de faturamento anteriores
        $rand = random_int(10000000, 99999999);
        $notaFiscal->randFaturam = $rand;

        $notaFiscal->cStatLote = -100;
        $notaFiscal->xMotivoLote = 'AGUARDANDO FATURAMENTO';

        $this->notaFiscalEntityHandler->save($notaFiscal);

        $notaFiscal->setXmlNota($nfe->asXML());
        $notaFiscal->jsonData['xml_gerado'][] = $nfe->asXML();

        /** @var NotaFiscal $notaFiscal */
        $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
        return $notaFiscal;
    }

    /**
     * @param NotaFiscalItem $nfItem
     * @param \SimpleXMLElement $itemXML
     * @throws ViewException
     */
    public function handleImpostos($nfe, NotaFiscalItem $nfItem, \SimpleXMLElement $itemXML): void
    {
        $csosn = $nfItem->csosn;

        switch ($csosn) {
            // não contribuinte SIMPLES NACIONAL
            case null:
            {

                $cst = $nfItem->cst;
                if (!$cst) {
                    throw new ViewException('CST não informado para o item ' . $nfItem->ordem . ' (' . $nfItem->descricao . ')');
                }
                $tagICMS = 'ICMS' . $cst;

                if ($nfItem->icmsAliquota > 0) {
                    $itemXML->imposto->ICMS->$tagICMS->orig = '0';
                    $itemXML->imposto->ICMS->$tagICMS->CST = $cst;
                    $itemXML->imposto->ICMS->$tagICMS->modBC = (int)$nfItem->icmsModBC;
                    $itemXML->imposto->ICMS->$tagICMS->vBC = bcmul($nfItem->icmsValorBc, 1, 2);
                    $itemXML->imposto->ICMS->$tagICMS->pICMS = bcmul($nfItem->icmsAliquota, 1, 2);
                    $itemXML->imposto->ICMS->$tagICMS->vICMS = bcmul($nfItem->icmsValor, 1, 2);
                } else {
                    $itemXML->imposto->ICMS->$tagICMS->orig = '0';
                    $itemXML->imposto->ICMS->$tagICMS->CST = $cst;
                }

                $itemXML->imposto->IPI->cEnq = '999';
                $itemXML->imposto->IPI->IPINT->CST = '53';

                if ($nfItem->pisAliquota > 0) {
                    $itemXML->imposto->PIS->PISAliq->CST = '01';
                    $itemXML->imposto->PIS->PISAliq->vBC = bcmul($nfItem->pisValorBc, 1, 2);
                    $itemXML->imposto->PIS->PISAliq->pPIS = bcmul($nfItem->pisAliquota, 1, 2);
                    $itemXML->imposto->PIS->PISAliq->vPIS = bcmul($nfItem->pisValor, 1, 2);
                } else {
                    $itemXML->imposto->PIS->PISNT->CST = '04';
                }

                if ($nfItem->cofinsAliquota > 0) {
                    $itemXML->imposto->COFINS->COFINSAliq->CST = '01';
                    $itemXML->imposto->COFINS->COFINSAliq->vBC = bcmul($nfItem->cofinsValorBc, 1, 2);
                    $itemXML->imposto->COFINS->COFINSAliq->pCOFINS = bcmul($nfItem->cofinsAliquota, 1, 2);
                    $itemXML->imposto->COFINS->COFINSAliq->vCOFINS = bcmul($nfItem->cofinsValor, 1, 2);
                } else {
                    $itemXML->imposto->COFINS->COFINSNT->CST = '04';
                }

                if ($nfe->infNFe->dest->indIEDest == 9 && $nfe->infNFe->ide->indFinal == 1 && $nfe->infNFe->ide->idDest == 2) {
                    if ($itemXML->imposto->ICMS->$tagICMS->vBC) {
                        $itemXML->imposto->ICMSUFDest->vBCUFDest = $itemXML->imposto->ICMS->$tagICMS->vBC;

                        $itemXML->imposto->ICMSUFDest->vBCFCPUFDest = 0.00;
                        $itemXML->imposto->ICMSUFDest->pFCPUFDest = 0.0000;
                        $icmsUFDest = 17;

                        switch ($nfe->infNFe->dest->enderDest->UF) {
                            case 'AC':
                            case 'AL':
                            case 'ES':
                            case 'GO':
                            case 'MT':
                            case 'MS':
                            case 'PA':
                            case 'PI':
                            case 'RS':
                            case 'RR':
                            case 'SC':
                                $icmsUFDest = 17;
                                break;
                            case 'AM':
                            case 'AP':
                            case 'BA':
                            case 'CE':
                            case 'DF':
                            case 'MA':
                            case 'MG':
                            case 'PB':
                            case 'PE':
                            case 'RN':
                            case 'RJ':
                            case 'SP':
                            case 'SE':
                            case 'TO':
                                $icmsUFDest = 18;
                                break;
                            case 'RO':
                                $icmsUFDest = 17.5;
                                break;
                            case 'PR':
                                throw new ViewException('nfe->infNFe->dest->enderDest->UF não pode ser "PR"');
                        }

                        $itemXML->imposto->ICMSUFDest->pICMSUFDest = number_format($icmsUFDest, 2, '.', '');
                        $itemXML->imposto->ICMSUFDest->pICMSInter = $itemXML->imposto->ICMS->$tagICMS->pICMS;
                        $itemXML->imposto->ICMSUFDest->pICMSInterPart = 100.00;
                        $itemXML->imposto->ICMSUFDest->vFCPUFDest = 0.00;

                        $calcICMS = bcdiv(bcsub($icmsUFDest, $itemXML->imposto->ICMS->$tagICMS->pICMS, 2), 100, 2);
                        $vICMS = number_format(bcmul($itemXML->imposto->ICMS->$tagICMS->vBC, $calcICMS, 2), 2, '.', '');

                        $itemXML->imposto->ICMSUFDest->vICMSUFDest = $vICMS;
                        $itemXML->imposto->ICMSUFDest->vICMSUFRemet = 0.00;
                    }
                }
                break;
            }
            case 900:
            {
                $itemXML->imposto->ICMS->ICMSSN900->orig = '0';
                $itemXML->imposto->ICMS->ICMSSN900->CSOSN = 900;
                $itemXML->imposto->ICMS->ICMSSN900->modBC = '0';
                $itemXML->imposto->ICMS->ICMSSN900->vBC = number_format(abs($nfItem->icmsValorBc), 2, '.', '');
                $itemXML->imposto->ICMS->ICMSSN900->pICMS = bcmul($nfItem->icmsAliquota, 1, 2);
                $itemXML->imposto->ICMS->ICMSSN900->vICMS = number_format(abs($nfItem->icmsValor), 2, '.', '');

                $itemXML->imposto->PIS->PISAliq->CST = '01';
                $itemXML->imposto->PIS->PISAliq->vBC = '0.00';
                $itemXML->imposto->PIS->PISAliq->pPIS = '0.0000';
                $itemXML->imposto->PIS->PISAliq->vPIS = '0.00';

                $itemXML->imposto->COFINS->COFINSAliq->CST = '01';
                $itemXML->imposto->COFINS->COFINSAliq->vBC = '0.00';
                $itemXML->imposto->COFINS->COFINSAliq->pCOFINS = '0.000';
                $itemXML->imposto->COFINS->COFINSAliq->vCOFINS = '0.00';

                break;
            }
            case 103:
            default:
            {
                $itemXML->imposto->ICMS->ICMSSN102->orig = '0';
                $itemXML->imposto->ICMS->ICMSSN102->CSOSN = $nfItem->csosn;
                $itemXML->imposto->PIS->PISNT->CST = '07';
                $itemXML->imposto->COFINS->COFINSNT->CST = '07';
                break;
            }
        }

    }


    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws ViewException
     */
    public function enviaNFe(NotaFiscal $notaFiscal): NotaFiscal
    {
        try {
            $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->documentoEmitente);
            $tools->model($notaFiscal->tipoNotaFiscal === 'NFE' ? '55' : '65');

            if (!$notaFiscal->getXMLDecoded()) {
                throw new ViewException('Impossível enviar NFe. XMLDecoded n/d.');
            }

            if (!isset($notaFiscal->getXMLDecoded()->infNFe->Signature) && !isset($notaFiscal->getXMLDecoded()->Signature)) {
                $xmlAssinado = $tools->signNFe($notaFiscal->getXmlNota());
                $notaFiscal->jsonData['xml_assinado'][] = $xmlAssinado;
                $notaFiscal->setXmlNota($xmlAssinado);
                $this->notaFiscalEntityHandler->save($notaFiscal);
            } else {
                $xmlAssinado = $notaFiscal->getXmlNota();
            }

            $idLote = random_int(1000000000000, 9999999999999);
            $sincrono = $notaFiscal->tipoNotaFiscal === 'NFCE' ? 1 : 0;
            $resp = $tools->sefazEnviaLote([$xmlAssinado], $idLote, $sincrono);
            $st = new Standardize();
            $std = $st->toStd($resp);
            $notaFiscal->cStatLote = $std->cStat;
            $notaFiscal->xMotivoLote = $std->xMotivo;
            if ((string)$std->cStat === '103') {
                $notaFiscal->nRec = $std->infRec->nRec;
            }
            $this->notaFiscalEntityHandler->save($notaFiscal);

            if (!$sincrono) {
                $tentativa = 1;
                while (true) {
                    $this->consultaRecibo($notaFiscal);
                    if (!$notaFiscal->cStat || (int)$notaFiscal->cStat === -100) {
                        sleep(1);
                        if (++$tentativa === 4) break;
                    } else {
                        break;
                    }
                }
            } else {
                try {
                    // Para notas síncronas não precisa consultar depois o protocolo, portanto a lógica é diferente
                    // da consultaRecibo()
                    $notaFiscal->cStat = $std->protNFe->infProt->cStat;
                    $notaFiscal->xMotivo = $std->protNFe->infProt->xMotivo;

                    if ($notaFiscal->getXMLDecoded()->getName() !== 'nfeProc') {
                        try {
                            $r = Complements::toAuthorize($notaFiscal->getXmlNota(), $resp);
                            $notaFiscal->jsonData['xml_toAuthorize'][] = $r;
                            $notaFiscal->setXmlNota($r);
                        } catch (\Exception $e) {
                            $this->syslog->error($e->getMessage());
                            $this->syslog->error('Erro no Complements::toAuthorize para $notaFiscal->id = ' . $notaFiscal->getId());
                        }
                    }
                    if (in_array($std->protNFe->infProt->cStat, ['100', '302'])) { //DENEGADAS
                        $notaFiscal->protocoloAutorizacao = $std->protNFe->infProt->nProt;
                        $notaFiscal->dtProtocoloAutorizacao = DateTimeUtils::parseDateStr($std->protNFe->infProt->dhRecbto);
                    }
                } catch (\Throwable $e) {
                    $this->syslog->error('consultaRecibo - Id: ' . $notaFiscal->getId());
                    $this->syslog->error($e->getMessage());
                    throw new ViewException('Erro ao setar info de transmissão síncrona');
                }
            }
            $this->notaFiscalEntityHandler->save($notaFiscal);
            return $notaFiscal;
        } catch (\Throwable $e) {
            $this->syslog->error('enviaNFe - id: ' . $notaFiscal->getId());
            $this->syslog->error($e->getMessage());
            $msg = 'Erro ao enviar a NFe';
            if ($e instanceof ValidatorException) {
                $msg .= ' (' . $e->getMessage() . ')';
            }
            throw new ViewException($msg);
        }
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws ViewException
     */
    public function consultarStatus(NotaFiscal $notaFiscal): NotaFiscal
    {
        //$content = conteúdo do certificado PFX
        $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->documentoEmitente);
        $tools->model($notaFiscal->tipoNotaFiscal === 'NFE' ? '55' : '65');
        //consulta número de recibo
        //$numeroRecibo = número do recíbo do envio do lote
        $tpAmb = $notaFiscal->ambiente === 'PROD' ? '1' : '2';
        $xmlResp = $tools->sefazConsultaChave($notaFiscal->chaveAcesso, $tpAmb);
        $notaFiscal->jsonData['xmlResp_sefazConsultaChave'][] = $xmlResp;
        //transforma o xml de retorno em um stdClass
        $st = new Standardize();
        $std = $st->toStd($xmlResp);

        $notaFiscal->cStatLote = $std->cStat;
        $notaFiscal->xMotivoLote = $std->xMotivo;

        $this->addHistorico($notaFiscal, $std->cStat ?? -1, 'sefazConsultaChave', $xmlResp);

        if ($std->cStat === '104' || $std->cStat === '100') { //lote processado (tudo ok)
            $cStat = $std->protNFe->infProt->cStat;
            $notaFiscal->cStat = $cStat;
            $notaFiscal->xMotivo = $std->protNFe->infProt->xMotivo;
            if ($notaFiscal->getXmlNota() && $notaFiscal->getXMLDecoded() && $notaFiscal->getXMLDecoded()->getName() !== 'nfeProc') {
                try {
                    if (!isset($notaFiscal->getXMLDecoded()->infNFe->Signature) &&
                        !isset($notaFiscal->getXMLDecoded()->Signature)) {
                        $xmlAssinado = $tools->signNFe($notaFiscal->getXmlNota());
                        $notaFiscal->jsonData['xml_assinado'][] = $xmlAssinado;
                        $notaFiscal->setXmlNota($xmlAssinado);
                        $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
                    }
                    $r = Complements::toAuthorize($notaFiscal->getXmlNota(), $xmlResp);
                    $notaFiscal->jsonData['xml_toAuthorize'][] = $r;
                    $notaFiscal->setXmlNota($r);
                } catch (\Exception $e) {
                    $this->syslog->error($e->getMessage());
                    $this->syslog->error('Erro no Complements::toAuthorize para $notaFiscal->id = ' . $notaFiscal->getId());
                }
            }
            if (in_array($cStat, ['100', '302'])) { //DENEGADAS
                $notaFiscal->protocoloAutorizacao = $std->protNFe->infProt->nProt;
                $notaFiscal->dtProtocoloAutorizacao = DateTimeUtils::parseDateStr($std->protNFe->infProt->dhRecbto);
            }
        } else if ($std->cStat === 217) {
            $this->consultaRecibo($notaFiscal);
        }
        /** @var NotaFiscal $notaFiscal */
        $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
        return $notaFiscal;
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws ViewException
     * @throws \Exception
     */
    public function cancelar(NotaFiscal $notaFiscal)
    {
        if ($notaFiscal->cStat !== 100 && $notaFiscal->cStat !== 204) {
            throw new \RuntimeException('Nota Fiscal com status diferente de \'100\' ou de \'204\' não pode ser cancelada. (id: ' . $notaFiscal->getId() . ')');
        }

        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->documentoEmitente);
        if ($notaFiscal->documentoEmitente !== $nfeConfigs['cnpj']) {
            throw new ViewException('Documento Emitente diferente do CNPJ configurado');
        }

        $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->documentoEmitente);
        $tools->model($notaFiscal->tipoNotaFiscal === 'NFE' ? '55' : '65');

        $chaveNota = $notaFiscal->chaveAcesso;
        $xJust = $notaFiscal->motivoCancelamento;
        $nProt = $notaFiscal->protocoloAutorizacao;
        $response = $tools->sefazCancela($chaveNota, $xJust, $nProt);

        $stdCl = new Standardize($response);
        $std = $stdCl->toStd();

        //verifique se o evento foi processado
        if ((string)$std->cStat !== '128') { // Processamento do Lote – o lote foi processado (cStat=128)
            $notaFiscal->cStat = $std->cStat;
            $notaFiscal->xMotivo = $std->retEvento->infEvento->xMotivo;
            /** @var NotaFiscal $notaFiscal */
            $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
        } else {
            $cStat = $std->retEvento->infEvento->cStat;
            /**
             * 101 - Cancelamento de NF-e homologado
             * 135 - Evento registrado e vinculado a NF-e
             * 155 - Cancelamento homologado fora de prazo”.
             */
            if ($cStat == '101' || $cStat == '155' || $cStat == '135') {
                $xml = Complements::toAuthorize($tools->lastRequest, $response);

                $notaFiscal->cStat = $cStat;
                $notaFiscal->xMotivo = $std->retEvento->infEvento->xMotivo;
                $notaFiscal->jsonData['dt_cancelamento'] = $std->retEvento->infEvento->dhRegEvento;
                /** @var NotaFiscal $notaFiscal */
                $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);

                $evento = new NotaFiscalEvento();
                $evento->notaFiscal = $notaFiscal;
                $evento->setXml($xml);
                $evento->descEvento = 'CANCELAMENTO';
                $evento->nSeqEvento = 1;
                $evento->tpEvento = 110111;
                $this->notaFiscalEventoEntityHandler->save($evento);
            } else {
                $notaFiscal->cStat = $cStat;
                $notaFiscal->xMotivo = $std->retEvento->infEvento->xMotivo;
                /** @var NotaFiscal $notaFiscal */
                $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
            }
        }

        return $notaFiscal;
    }

    /**
     * @param NotaFiscalCartaCorrecao $cartaCorrecao
     * @return NotaFiscalCartaCorrecao
     * @throws ViewException
     * @throws \Exception
     */
    public function cartaCorrecao(NotaFiscalCartaCorrecao $cartaCorrecao): NotaFiscalCartaCorrecao
    {
        $tools = $this->nfeUtils->getToolsByCNPJ($cartaCorrecao->notaFiscal->documentoEmitente);
        $tools->model($cartaCorrecao->notaFiscal->tipoNotaFiscal === 'NFE' ? '55' : '65');

        $chave = $cartaCorrecao->notaFiscal->chaveAcesso;
        $nSeqEvento = $cartaCorrecao->seq;

        $response = $tools->sefazCCe($chave, $cartaCorrecao->cartaCorrecao, $nSeqEvento);

        $stdCl = new Standardize($response);
        $std = $stdCl->toStd();

        //verifique se o evento foi processado
        if ($std->cStat != 128) {
            $this->syslog->error('Erro ao enviar carta de correção');
            $this->syslog->error('$std->cStat != 128');
        } else {
            $cStat = $std->retEvento->infEvento->cStat;
            if ($cStat == '135' || $cStat == '136') {
                //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
                $xml = Complements::toAuthorize($tools->lastRequest, $response);
                $cartaCorrecao->msgRetorno = $xml;
                $cartaCorrecao = $this->notaFiscalCartaCorrecaoEntityHandler->save($cartaCorrecao);
            } else {
                $this->syslog->error('Erro ao enviar carta de correção');
                $this->syslog->error('cStat: ' . $cStat);
            }
        }

        return $cartaCorrecao;

    }

    /**
     * @param string $cnpj
     * @param string $uf
     * @return mixed
     * @throws ViewException
     */
    public function consultarCNPJ(string $cnpj, string $uf)
    {
        try {
            $tools = $this->nfeUtils->getToolsEmUso();
            $iest = '';
            $cpf = '';
            $response = $tools->sefazCadastro($uf, $cnpj, $iest, $cpf);
            $xmlResp = simplexml_load_string($response);
            $xmlResp->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
            $xml = $xmlResp->xpath('//soap:Body');
            if (!($xml[0] ?? null)) {
                throw new \RuntimeException('Erro no retorno da consulta ao CNPJ (' . $response . ')');
            }
            return $xml[0]->nfeResultMsg->retConsCad->infCons ?? $xml[0]->consultaCadastro4Result->retConsCad->infCons ?? $xml[0]->nfeResultMsg->consultaCadastroResult->retConsCad->infCons ?? null;
        } catch (\Exception $e) {
            $msg = ExceptionUtils::treatException($e);
            $this->syslog->error($msg);
            $this->syslog->error($e->getTraceAsString());
            throw new ViewException('Erro ao consultar o CNPJ (' . $msg . ')', 0, $e);
        }
    }


    /**
     * @param NotaFiscal $notaFiscal
     * @param int $codManifest
     * @return void
     * @throws ViewException
     */
    public function manifestar(NotaFiscal $notaFiscal, int $codManifest)
    {
        try {
            // Código do evento:
            // 210200 - Confirmação da Operação
            // 210210 - Ciência da Operação
            // 210220 - Desconhecimento da Operação
            // 210240 - Operação não Realizada

            $tpEvento = $codManifest; //ciencia da operação
            $xJust = ''; //a ciencia não requer justificativa
            $nSeqEvento = 1;

            $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->documentoDestinatario);

            $response = $tools->sefazManifesta($notaFiscal->chaveAcesso, $tpEvento, $xJust, $nSeqEvento);
            $st = new Standardize($response);

            $cStat = null;

            $retorno = null;

            try {
                $cStat = (int)$st->simpleXml()->retEvento->infEvento->cStat;
                $retorno = $st->simpleXml()->retEvento->infEvento->cStat . ' - ' . $st->simpleXml()->retEvento->infEvento->xMotivo;
            } catch (\Exception $e) {
                $cStat = 0;
            }

            $notaFiscal->dtManifestDest = new \DateTime();

            if ((int)$cStat === 135) {
                $operacoes =
                    [
                        210210 => '210210 - CIÊNCIA DA OPERAÇÃO',
                        210200 => '210200 - CONFIRMAÇÃO DA OPERAÇÃO',
                        210220 => '210220 - DESCONHECIMENTO DA OPERAÇÃO',
                        210240 => '210240 - OPERAÇÃO NÃO REALIZADA',
                    ];

                $notaFiscal->manifestDest = $operacoes[$codManifest];
            } else {
                $notaFiscal->manifestDest = $retorno;
            }
            $notaFiscal->jsonData['retorno_manifest'] = $retorno;

            $this->notaFiscalEntityHandler->save($notaFiscal);

        } catch (\Exception $e) {
            $this->syslog->error('Erro ao processar XML');
            $this->syslog->error($e->getMessage());
            throw new ViewException('Erro ao manifestar DFe (chave: ' . $notaFiscal->chaveAcesso . ')');
        }
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return void
     * @throws ViewException
     */
    public function consultaChave(NotaFiscal $notaFiscal)
    {
        try {
            $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->documentoEmitente);
            $tools->model($notaFiscal->tipoNotaFiscal === 'NFE' ? '55' : '65');
            $response = $tools->sefazConsultaChave($notaFiscal->chaveAcesso);

            //você pode padronizar os dados de retorno atraves da classe abaixo
            //de forma a facilitar a extração dos dados do XML
            //NOTA: mas lembre-se que esse XML muitas vezes será necessário,
            //      quando houver a necessidade de protocolos
            $stdCl = new Standardize($response);
            //nesse caso $std irá conter uma representação em stdClass do XML
            $std = $stdCl->toStd();
            //nesse caso o $arr irá conter uma representação em array do XML
            $arr = $stdCl->toArray();
            //nesse caso o $json irá conter uma representação em JSON do XML
            $json = $stdCl->toJson();


        } catch (\Exception $e) {
            $this->syslog->error('Erro ao processar XML');
            $this->syslog->error($e->getMessage());
            throw new ViewException('Erro ao consultaChaveDFe (chave: ' . $notaFiscal->chaveAcesso . ')');
        }
    }


    /**
     * @throws \Exception
     */
    public function deletarNaoNotas(): void
    {
        // Obtém todas as fis_nf que não tenham dtEmissao (arbitrário)
        /** @var NotaFiscalRepository $repo */
        $repo = $this->doctrine->getRepository(NotaFiscal::class);
        $nfes = $repo->findNotasNaoProcessadas();

        $idsADeletar = [];

        /** @var NotaFiscal $nf */
        foreach ($nfes as $nf) {
            try {
                $xml = $nf->getXMLDecoded();

                if ($xml->getName() === 'nfeProc') {
                    continue;
                }

                if ($xml->getName() === 'resNFe') {
                    continue;
                }

                if ($xml->getName() === 'resEvento') {
                    $idsADeletar[] = $nf->getId();
                    continue;
                }


                throw new ViewException('XML inválido (fis_nf.id = ' . $nf->getId() . ')');
            } catch (\Exception $e) {
                $this->syslog->error('Erro ao fazer o parse do xml para NF (chave: ' . $nf->chaveAcesso . ')');
            }
        }

        foreach ($idsADeletar as $id) {
            $this->notaFiscalEntityHandler->delete($repo->find($id));
        }

    }


    /**
     * @param string $tipoNotaFiscal
     * @param int $serie
     * @param int $numero
     * @return array
     */
    public function inutilizaNumeracao(string $cnpjEmitente, string $tipoNotaFiscal, int $serie, int $numero)
    {
        try {
            $tools = $this->nfeUtils->getToolsByCNPJ($cnpjEmitente);
            $tools->model($tipoNotaFiscal === 'NFE' ? '55' : '65');
            $xJust = 'Erro de digitação dos números sequencias das notas';
            $response = $tools->sefazInutiliza($serie, $numero, $numero, $xJust, 1);
            //$stdCl = new Standardize($response);
            //return $stdCl->toArray();
            return $response;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @throws ViewException
     */
    public function consultaRecibo(NotaFiscal $notaFiscal)
    {
        try {
            if (!$notaFiscal->nRec) {
                throw new ViewException('nRec N/D');
            }
            if (!$notaFiscal->getXMLDecoded()) {
                throw new ViewException('Impossível consultar recibo. XMLDecoded n/d.');
            }
            $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->documentoEmitente);
            $tools->model($notaFiscal->tipoNotaFiscal === 'NFE' ? '55' : '65');
            $xmlResp = $tools->sefazConsultaRecibo($notaFiscal->nRec);
            $std = (new Standardize($xmlResp))->toStd();
            $notaFiscal->cStatLote = $std->cStat;
            $this->addHistorico($notaFiscal, $std->cStat ?? -1, 'sefazConsultaRecibo', $xmlResp);
            $notaFiscal->xMotivoLote = $std->xMotivo;
            if ((int)$std->cStat === 104 || (int)$std->cStat === 100) { //lote processado (tudo ok)
                $cStat = $std->protNFe->infProt->cStat;
                $notaFiscal->cStat = $cStat;
                $notaFiscal->xMotivo = $std->protNFe->infProt->xMotivo;
                if ($notaFiscal->getXmlNota() && $notaFiscal->getXMLDecoded() && $notaFiscal->getXMLDecoded()->getName() !== 'nfeProc') {
                    try {
                        if (!isset($notaFiscal->getXMLDecoded()->infNFe->Signature) &&
                            !isset($notaFiscal->getXMLDecoded()->Signature)) {
                            $xmlAssinado = $tools->signNFe($notaFiscal->getXmlNota());
                            $notaFiscal->jsonData['xml_assinado'][] = $xmlAssinado;
                            $notaFiscal->setXmlNota($xmlAssinado);
                            $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
                        }
                        $r = Complements::toAuthorize($notaFiscal->getXmlNota(), $xmlResp);
                        $notaFiscal->jsonData['xml_toAuthorize'][] = $r;
                        $notaFiscal->setXmlNota($r);
                    } catch (\Exception $e) {
                        $this->syslog->error($e->getMessage());
                        $this->syslog->error('Erro no Complements::toAuthorize para $notaFiscal->id = ' . $notaFiscal->getId());
                    }
                }
                if (in_array($cStat, ['100', '302'])) { //DENEGADAS
                    $notaFiscal->protocoloAutorizacao = $std->protNFe->infProt->nProt;
                    $notaFiscal->dtProtocoloAutorizacao = DateTimeUtils::parseDateStr($std->protNFe->infProt->dhRecbto);
                }
            }
            $this->notaFiscalEntityHandler->save($notaFiscal);
        } catch (\Throwable $e) {
            $this->syslog->error('consultaRecibo - Id: ' . $notaFiscal->getId());
            $this->syslog->error($e->getMessage());
            throw new ViewException('Erro ao consultar recibo');
        }
    }


    /**
     * @param NotaFiscal $notaFiscal
     * @param $codigoStatus
     * @param $descricao
     * @param null $obs
     * @throws ViewException
     */
    public function addHistorico(NotaFiscal $notaFiscal, $codigoStatus, $descricao, $obs = null): void
    {
        try {
            $conn = $this->notaFiscalEntityHandler->getDoctrine()->getConnection();
            $conn->insert('fis_nf_historico', [
                'dt_historico' => (new \DateTime())->format('Y-m-d H:i:s'),
                'codigo_status' => $codigoStatus,
                'descricao' => $descricao,
                'obs' => substr($obs, 0, 20000),
                'fis_nf_id' => $notaFiscal->getId(),
                'inserted' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated' => (new \DateTime())->format('Y-m-d H:i:s'),
                'version' => 0,
                'estabelecimento_id' => 1,
                'user_inserted_id' => 1,
                'user_updated_id' => 1,
            ]);
        } catch (\Throwable $e) {
            throw new ViewException('Erro ao inserir fis_nf_historico');
        }

    }
}
