<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Entity\Base\Municipio;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Base\MunicipioRepository;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
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


    /**
     * @param EntityManagerInterface $doctrine
     * @param NotaFiscalEntityHandler $notaFiscalEntityHandler
     * @param NotaFiscalEventoEntityHandler $notaFiscalEventoEntityHandler
     * @param NotaFiscalCartaCorrecaoEntityHandler $notaFiscalCartaCorrecaoEntityHandler
     * @param LoggerInterface $logger
     * @param NFeUtils $nfeUtils
     * @param ParameterBagInterface $params
     */
    public function __construct(EntityManagerInterface $doctrine,
                                NotaFiscalEntityHandler $notaFiscalEntityHandler,
                                NotaFiscalEventoEntityHandler $notaFiscalEventoEntityHandler,
                                NotaFiscalCartaCorrecaoEntityHandler $notaFiscalCartaCorrecaoEntityHandler,
                                LoggerInterface $logger,
                                NFeUtils $nfeUtils,
                                ParameterBagInterface $params)
    {
        $this->doctrine = $doctrine;
        $this->notaFiscalEntityHandler = $notaFiscalEntityHandler;
        $this->notaFiscalEventoEntityHandler = $notaFiscalEventoEntityHandler;
        $this->notaFiscalCartaCorrecaoEntityHandler = $notaFiscalCartaCorrecaoEntityHandler;
        $this->logger = $logger;
        $this->nfeUtils = $nfeUtils;
        $this->params = $params;
    }


    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws ViewException
     */
    public function gerarXML(NotaFiscal $notaFiscal): NotaFiscal
    {
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

        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->getDocumentoEmitente());


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


        $nfe->infNFe->ide->nNF = $notaFiscal->getNumero();

        $nfe->infNFe->ide->cNF = $notaFiscal->getCnf();

        $nfe->infNFe->ide->mod = TipoNotaFiscal::get($notaFiscal->getTipoNotaFiscal())['codigo'];
        $nfe->infNFe->ide->serie = $notaFiscal->getSerie();

        $tpEmis = 1;
        $nfe->infNFe->ide->tpEmis = $tpEmis;

        $nfe->infNFe['Id'] = 'NFe' . $notaFiscal->getChaveAcesso();
        $nfe->infNFe->ide->cDV = NFeKeys::verifyingDigit(substr($notaFiscal->getChaveAcesso(), 0, -1));

        $nfe->infNFe->ide->natOp = $notaFiscal->getNaturezaOperacao();

        $nfe->infNFe->ide->dhEmi = $notaFiscal->getDtEmissao()->format('Y-m-d\TH:i:sP');

        $nfe->infNFe->ide->tpNF = $notaFiscal->getEntradaSaida() === 'E' ? '0' : '1';

        $finNFe = FinalidadeNF::get($notaFiscal->getFinalidadeNf())['codigo'];
        $nfe->infNFe->ide->finNFe = $finNFe;

        // Devolução
        if ($finNFe === 4) {
            if (!$notaFiscal->getA03idNfReferenciada()) {
                throw new \RuntimeException('Nota fiscal de devolução sem Id NF Referenciada.');
            }
            // else
            $nfe->infNFe->ide->NFref->refNFe = $notaFiscal->getA03idNfReferenciada();
        }

        if ($notaFiscal->getTipoNotaFiscal() === 'NFE') {
            $nfe->infNFe->ide->dhSaiEnt = $notaFiscal->getDtSaiEnt()->format('Y-m-d\TH:i:sP');
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
        if ($notaFiscal->getDocumentoDestinatario()) {

            if (strlen($notaFiscal->getDocumentoDestinatario()) === 14) {
                $nfe->infNFe->dest->CNPJ = preg_replace("/[^0-9]/", '', $notaFiscal->getDocumentoDestinatario());
            } else {
                $nfe->infNFe->dest->CPF = preg_replace("/[^0-9]/", '', $notaFiscal->getDocumentoDestinatario());
            }

            if ($notaFiscal->getAmbiente() === 'HOM') {
                $nfe->infNFe->dest->xNome = 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
            } else {
                $nfe->infNFe->dest->xNome = trim($notaFiscal->getXNomeDestinatario());
            }

            if ($notaFiscal->getTipoNotaFiscal() === 'NFE') {

                if ($notaFiscal->jsonData['idDest'] ?? false) {
                    $idDest = $notaFiscal->jsonData['idDest'];
                } else {
                    if (($notaFiscal->getEstadoDestinatario() === $nfeConfigs['siglaUF']) || ($nfeConfigs[' t_sempre1'] ?? false)) {
                        $idDest = 1;
                    } else {
                        $idDest = 2;
                    }
                }
                $nfe->infNFe->ide->idDest = $idDest; // $nfe->infNFe->ide->idDest = 2;

                $nfe->infNFe->dest->enderDest->xLgr = trim($notaFiscal->getLogradouroDestinatario());
                $nfe->infNFe->dest->enderDest->nro = trim($notaFiscal->getNumeroDestinatario());
                if ($notaFiscal->complementoDestinatario) {
                    $nfe->infNFe->dest->enderDest->xCpl = trim($notaFiscal->complementoDestinatario);
                }
                $nfe->infNFe->dest->enderDest->xBairro = trim($notaFiscal->getBairroDestinatario());


                /** @var MunicipioRepository $repoMunicipio */
                $repoMunicipio = $this->doctrine->getRepository(Municipio::class);

                /** @var Municipio $r */
                $r = $repoMunicipio->findOneByFiltersSimpl([
                    ['municipioNome', 'EQ', $notaFiscal->getCidadeDestinatario()],
                    ['ufSigla', 'EQ', $notaFiscal->getEstadoDestinatario()]
                ]);

                if (!$r ||
                    strtoupper(StringUtils::removerAcentos($r->getMunicipioNome())) !== strtoupper(StringUtils::removerAcentos($notaFiscal->getCidadeDestinatario()))) {
                    throw new ViewException('Município inválido: [' . $notaFiscal->getCidadeDestinatario() . '-' . $notaFiscal->getEstadoDestinatario() . ']');
                }

                $nfe->infNFe->dest->enderDest->cMun = $r->getMunicipioCodigo();
                $nfe->infNFe->dest->enderDest->xMun = $r->getMunicipioNome();
                $nfe->infNFe->dest->enderDest->UF = $r->getUfSigla();


                $nfe->infNFe->dest->enderDest->CEP = preg_replace('/\D/', '', $notaFiscal->getCepDestinatario());
                $nfe->infNFe->dest->enderDest->cPais = 1058;
                $nfe->infNFe->dest->enderDest->xPais = 'BRASIL';
                if (trim($notaFiscal->getFoneDestinatario())) {
                    $nfe->infNFe->dest->enderDest->fone = preg_replace('/\D/', '', $notaFiscal->getFoneDestinatario());
                }
            }


            // 1=Contribuinte ICMS (informar a IE do destinatário);
            // 2=Contribuinte isento de Inscrição no cadastro de Contribuintes do ICMS;
            // 9=Não Contribuinte, que pode ou não possuir Inscrição Estadual no Cadastro de Contribuintes do ICMS.
            // Nota 1: No caso de NFC-e informar indIEDest=9 e não informar a tag IE do destinatário;
            // Nota 2: No caso de operação com o Exterior informar indIEDest=9 e não informar a tag IE do destinatário;
            // Nota 3: No caso de Contribuinte Isento de Inscrição (indIEDest=2), não informar a tag IE do destinatário.

            if ($notaFiscal->getTipoNotaFiscal() === 'NFCE') {
                $nfe->infNFe->dest->indIEDest = 9;
                unset($nfe->infNFe->transp);
                unset($nfe->infNFe->dest->IE);
            } else {
                if (($notaFiscal->getInscricaoEstadualDestinatario() === 'ISENTO') || !$notaFiscal->getInscricaoEstadualDestinatario()) {
                    unset($nfe->infNFe->dest->IE);
                    // Rejeição 805: A SEFAZ do destinatário não permite Contribuinte Isento de Inscrição Estadual
                    if (in_array($notaFiscal->getEstadoDestinatario(), ['AM', 'BA', 'CE', 'GO', 'MG', 'MS', 'MT', 'PA', 'PE', 'RN', 'SE', 'SP'])) {
                        $nfe->infNFe->dest->indIEDest = 9;
                        if (strlen($notaFiscal->getDocumentoDestinatario()) === 11) {
                            $nfe->infNFe->ide->indFinal = 1; // nesses casos, sendo CPF considera sempre como consumidor final
                        }
                    } else {
                        $nfe->infNFe->dest->indIEDest = 2;
                    }
                } else {
                    $nfe->infNFe->dest->indIEDest = 1;
                    if ($notaFiscal->getInscricaoEstadualDestinatario()) {
                        $nfe->infNFe->dest->IE = trim($notaFiscal->getInscricaoEstadualDestinatario());
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

        if ($notaFiscal->getTipoNotaFiscal() === 'NFCE') {
            $nfe->infNFe->ide->tpImp = 4;
        } else {
            $nfe->infNFe->ide->tpImp = 1;
        }

        // 1=Produção
        // 2=Homologação
        $nfe->infNFe->ide->tpAmb = $notaFiscal->getAmbiente() === 'PROD' ? 1 : 2;

        unset($nfe->infNFe->det);
        $i = 1;

        $total_bcICMS = 0;
        $total_vICMS = 0;

        $total_vPIS = 0;

        $total_vCOFINS = 0;

        /** @var NotaFiscalItem $nfItem */
        foreach ($notaFiscal->getItens() as $nfItem) {
            $itemXML = $nfe->infNFe->addChild('det');
            $itemXML['nItem'] = $nfItem->getOrdem();
            $itemXML->prod->cProd = $nfItem->getCodigo();
            $itemXML->prod->cEAN = 'SEM GTIN';

            if ($notaFiscal->getAmbiente() === 'HOM' && $i === 1) {
                $xProd = 'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
            } else {
                $xProd = $nfItem->getDescricao();
            }

            $itemXML->prod->xProd = $xProd;
            $itemXML->prod->NCM = $nfItem->getNcm();
            if ($nfItem->getCest()) {
                $itemXML->prod->CEST = $nfItem->getCest();
            }
            $itemXML->prod->CFOP = $nfItem->getCfop();
            $itemXML->prod->uCom = $nfItem->getUnidade();
            $itemXML->prod->qCom = $nfItem->getQtde();
            $itemXML->prod->vUnCom = $nfItem->getValorUnit();
            $itemXML->prod->vProd = number_format($nfItem->getValorTotal(), 2, '.', '');
            $itemXML->prod->cEANTrib = 'SEM GTIN';
            $itemXML->prod->uTrib = $nfItem->getUnidade();
            $itemXML->prod->qTrib = $nfItem->getQtde();
            $itemXML->prod->vUnTrib = number_format($nfItem->getValorUnit(), 2, '.', '');
            if (bccomp($nfItem->getValorDesconto(), 0.00, 2)) {
                $itemXML->prod->vDesc = number_format(abs($nfItem->getValorDesconto()), 2, '.', '');
            }
            $itemXML->prod->indTot = '1';

            $this->handleImpostos($nfe, $nfItem, $itemXML);

            $total_bcICMS += $nfItem->getIcmsValorBc();
            $total_vICMS += $nfItem->getIcmsValor();

            $total_vPIS += $nfItem->getPisValor();

            $total_vCOFINS += $nfItem->getCofinsValor();

            // $itemXML->prod->vFrete = number_format($nfItem->jsonData['valor_frete_item'] ?? 0, 2, '.', '');

            $i++;
        }
        $nfe->infNFe->addChild('total');
        $nfe->infNFe->total->ICMSTot->vBC = number_format($total_bcICMS, 2, '.', '');
        $nfe->infNFe->total->ICMSTot->vICMS = number_format($total_vICMS, 2, '.', '');
        $nfe->infNFe->total->ICMSTot->vICMSDeson = '0.00';

        if ($nfe->infNFe->dest->indIEDest == 9 && $nfe->infNFe->ide->indFinal == 1 && $nfe->infNFe->ide->idDest == 2) {
            $nfe->infNFe->total->ICMSTot->vFCPUFDest = '0.00';
            $nfe->infNFe->total->ICMSTot->vICMSUFDest = $nfe->infNFe->total->ICMSTot->vICMS;
        }

        $nfe->infNFe->total->ICMSTot->vFCP = '0.00';
        $nfe->infNFe->total->ICMSTot->vBCST = '0.00';
        $nfe->infNFe->total->ICMSTot->vST = '0.00';
        $nfe->infNFe->total->ICMSTot->vFCPST = '0.00';
        $nfe->infNFe->total->ICMSTot->vFCPSTRet = '0.00';
        $nfe->infNFe->total->ICMSTot->vProd = number_format($notaFiscal->getSubtotal(), 2, '.', '');
        $nfe->infNFe->total->ICMSTot->vFrete = $notaFiscal->getTranspValorTotalFrete() ?? '0.00';
        $nfe->infNFe->total->ICMSTot->vSeg = '0.00';
        // if (bccomp($notaFiscal->getTotalDescontos(), 0.00, 2)) {
        $nfe->infNFe->total->ICMSTot->vDesc = number_format(abs($notaFiscal->getTotalDescontos()), 2, '.', '');
        // }
        $nfe->infNFe->total->ICMSTot->vII = '0.00';
        $nfe->infNFe->total->ICMSTot->vIPI = '0.00';
        $nfe->infNFe->total->ICMSTot->vIPIDevol = '0.00';
        $nfe->infNFe->total->ICMSTot->vPIS = number_format($total_vPIS, 2, '.', '');;
        $nfe->infNFe->total->ICMSTot->vCOFINS = number_format($total_vCOFINS, 2, '.', '');;
        $nfe->infNFe->total->ICMSTot->vOutro = '0.00';
        $nfe->infNFe->total->ICMSTot->vNF = number_format($notaFiscal->getValorTotal(), 2, '.', '');
        $nfe->infNFe->total->ICMSTot->vTotTrib = '0.00';

        if ($notaFiscal->getTipoNotaFiscal() === 'NFCE') {
            $nfe->infNFe->transp->modFrete = 9;

        } else {
            $nfe->infNFe->transp->modFrete = ModalidadeFrete::get($notaFiscal->getTranspModalidadeFrete())['codigo'];

            if ($notaFiscal->getTranspDocumento()) {

                $nfe->infNFe->transp->transporta->CNPJ = $notaFiscal->getTranspDocumento();
                $nfe->infNFe->transp->transporta->xNome = trim($notaFiscal->getTranspNome());
                if ($notaFiscal->getTranspInscricaoEstadual()) {
                    $nfe->infNFe->transp->transporta->IE = trim($notaFiscal->getTranspInscricaoEstadual());
                }

                $nfe->infNFe->transp->transporta->xEnder = substr($notaFiscal->getTranspEndereco(), 0, 60);

                if (!$notaFiscal->getTranspCidade() || !$notaFiscal->getTranspEstado()) {
                    throw new ViewException('Cidade/UF da transportadora n/d');
                }

                /** @var MunicipioRepository $repoMunicipio */
                $repoMunicipio = $this->doctrine->getRepository(Municipio::class);

                /** @var Municipio $r */
                $r = $repoMunicipio->findOneByFiltersSimpl([
                    ['municipioNome', 'EQ', $notaFiscal->getTranspCidade()],
                    ['ufSigla', 'EQ', $notaFiscal->getTranspEstado()]
                ]);

                if (!$r || strtoupper(StringUtils::removerAcentos($r->getMunicipioNome())) !== strtoupper(StringUtils::removerAcentos($notaFiscal->getTranspCidade()))) {
                    throw new ViewException('Município inválido: [' . $notaFiscal->getTranspCidade() . '-' . $notaFiscal->getTranspEstado() . ']');
                }


                $nfe->infNFe->transp->transporta->xMun = $r->getMunicipioNome();
                $nfe->infNFe->transp->transporta->UF = $r->getUfSigla();

                $nfe->infNFe->transp->vol->qVol = number_format($notaFiscal->getTranspQtdeVolumes(), 0);
                $nfe->infNFe->transp->vol->esp = $notaFiscal->getTranspEspecieVolumes();
                if ($notaFiscal->getTranspMarcaVolumes()) {
                    $nfe->infNFe->transp->vol->marca = $notaFiscal->getTranspMarcaVolumes();
                }
                if ($notaFiscal->getTranspNumeracaoVolumes()) {
                    $nfe->infNFe->transp->vol->nVol = $notaFiscal->getTranspNumeracaoVolumes();
                }

                $nfe->infNFe->transp->vol->pesoL = number_format($notaFiscal->getTranspPesoLiquido(), 3, '.', '');
                $nfe->infNFe->transp->vol->pesoB = number_format($notaFiscal->getTranspPesoBruto(), 3, '.', '');

            }
        }

        if ($finNFe === 3 or $finNFe === 4) {
            $nfe->infNFe->pag->detPag->tPag = '90';
            $nfe->infNFe->pag->detPag->vPag = '0.00';
        } else {
            $nfe->infNFe->pag->detPag->tPag = '01';
            $nfe->infNFe->pag->detPag->vPag = number_format($notaFiscal->getValorTotal(), 2, '.', '');
        }


        if ($notaFiscal->getInfoCompl()) {
            $infoCompl = preg_replace("/\r/", '', $notaFiscal->getInfoCompl());
            $infoCompl = preg_replace("/\n/", ';', $infoCompl);
            $nfe->infNFe->infAdic->infCpl = trim($infoCompl);
        }

        $nfe->infNFe->infRespTec->CNPJ = $nfeConfigs['cnpj'];
        $nfe->infNFe->infRespTec->xContato = $nfeConfigs['infRespTec_xContato'];
        $nfe->infNFe->infRespTec->email = $nfeConfigs['infRespTec_email'];
        $nfe->infNFe->infRespTec->fone = preg_replace('/\D/', '', $nfeConfigs['telefone']);


        // Número randômico para que não aconteça de pegar XML de retorno de tentativas de faturamento anteriores
        $rand = random_int(10000000, 99999999);
        $notaFiscal->setRandFaturam($rand);

        $notaFiscal->setCStatLote(-100);
        $notaFiscal->setXMotivoLote('AGUARDANDO FATURAMENTO');

        $this->notaFiscalEntityHandler->save($notaFiscal);

        $notaFiscal->setXmlNota($nfe->asXML());

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
        $csosn = $nfItem->getCsosn();

        switch ($csosn) {
            // não contribuinte SIMPLES NACIONAL
            case null:
            {

                $cst = $nfItem->getCst();
                if (!$cst) {
                    throw new ViewException('CST não informado para o item ' . $nfItem->getOrdem() . ' (' . $nfItem->getDescricao() . ')');
                }
                $tagICMS = 'ICMS' . $cst;

                if ($nfItem->getIcmsAliquota() > 0) {
                    $itemXML->imposto->ICMS->$tagICMS->orig = '0';
                    $itemXML->imposto->ICMS->$tagICMS->CST = $cst;
                    $itemXML->imposto->ICMS->$tagICMS->modBC = (int)$nfItem->getIcmsModBC();
                    $itemXML->imposto->ICMS->$tagICMS->vBC = bcmul($nfItem->getIcmsValorBc(), 1, 2);
                    $itemXML->imposto->ICMS->$tagICMS->pICMS = bcmul($nfItem->getIcmsAliquota(), 1, 2);
                    $itemXML->imposto->ICMS->$tagICMS->vICMS = bcmul($nfItem->getIcmsValor(), 1, 2);
                } else {
                    $itemXML->imposto->ICMS->$tagICMS->orig = '0';
                    $itemXML->imposto->ICMS->$tagICMS->CST = $cst;
                }

                $itemXML->imposto->IPI->cEnq = '999';
                $itemXML->imposto->IPI->IPINT->CST = '53';

                if ($nfItem->getPisAliquota() > 0) {
                    $itemXML->imposto->PIS->PISAliq->CST = '01';
                    $itemXML->imposto->PIS->PISAliq->vBC = bcmul($nfItem->getPisValorBc(), 1, 2);
                    $itemXML->imposto->PIS->PISAliq->pPIS = bcmul($nfItem->getPisAliquota(), 1, 2);
                    $itemXML->imposto->PIS->PISAliq->vPIS = bcmul($nfItem->getPisValor(), 1, 2);
                } else {
                    $itemXML->imposto->PIS->PISNT->CST = '04';
                }

                if ($nfItem->getCofinsAliquota() > 0) {
                    $itemXML->imposto->COFINS->COFINSAliq->CST = '01';
                    $itemXML->imposto->COFINS->COFINSAliq->vBC = bcmul($nfItem->getCofinsValorBc(), 1, 2);
                    $itemXML->imposto->COFINS->COFINSAliq->pCOFINS = bcmul($nfItem->getCofinsAliquota(), 1, 2);
                    $itemXML->imposto->COFINS->COFINSAliq->vCOFINS = bcmul($nfItem->getCofinsValor(), 1, 2);
                } else {
                    $itemXML->imposto->COFINS->COFINSNT->CST = '04';
                }

                if ($nfe->infNFe->dest->indIEDest == 9 && $nfe->infNFe->ide->indFinal == 1 && $nfe->infNFe->ide->idDest == 2) {
                    $itemXML->imposto->ICMSUFDest->vBCUFDest = $itemXML->imposto->ICMS->$tagICMS->vBC;
                    $itemXML->imposto->ICMSUFDest->vBCFCPUFDest = 0.00;
                    $itemXML->imposto->ICMSUFDest->pFCPUFDest = 0.0000;
                    $itemXML->imposto->ICMSUFDest->pICMSUFDest = '17.00';
                    $itemXML->imposto->ICMSUFDest->pICMSInter = $itemXML->imposto->ICMS->$tagICMS->pICMS;
                    $itemXML->imposto->ICMSUFDest->pICMSInterPart = 100.00;
                    $itemXML->imposto->ICMSUFDest->vFCPUFDest = 0.00;
                    $itemXML->imposto->ICMSUFDest->vICMSUFDest = $itemXML->imposto->ICMS->$tagICMS->vICMS;
                    $itemXML->imposto->ICMSUFDest->vICMSUFRemet = 0.00;
                }
                break;
            }
            case 900:
            {
                $itemXML->imposto->ICMS->ICMSSN900->orig = '0';
                $itemXML->imposto->ICMS->ICMSSN900->CSOSN = $nfItem->getCsosn();
                $itemXML->imposto->ICMS->ICMSSN900->modBC = '0';
                $itemXML->imposto->ICMS->ICMSSN900->vBC = number_format(abs($nfItem->getIcmsValorBc()), 2, '.', '');
                $itemXML->imposto->ICMS->ICMSSN900->pICMS = bcmul($nfItem->getIcmsAliquota(), 1, 2);
                $itemXML->imposto->ICMS->ICMSSN900->vICMS = number_format(abs($nfItem->getIcmsValor()), 2, '.', '');
                break;
            }
            case 103:
            default:
            {
                $itemXML->imposto->ICMS->ICMSSN102->orig = '0';
                $itemXML->imposto->ICMS->ICMSSN102->CSOSN = $nfItem->getCsosn();
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
            $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->getDocumentoEmitente());
            $tools->model($notaFiscal->getTipoNotaFiscal() === 'NFE' ? '55' : '65');
            if (!isset($notaFiscal->getXMLDecoded()->infNFe->Signature) && !isset($notaFiscal->getXMLDecoded()->Signature)) {
                $xmlAssinado = $tools->signNFe($notaFiscal->getXmlNota());
                $notaFiscal->setXmlNota($xmlAssinado);
                $this->notaFiscalEntityHandler->save($notaFiscal);
            } else {
                $xmlAssinado = $notaFiscal->getXmlNota();
            }
            $idLote = random_int(1000000000000, 9999999999999);
            $resp = $tools->sefazEnviaLote([$xmlAssinado], $idLote);//transforma o xml de retorno em um stdClass
            $st = new Standardize();
            $std = $st->toStd($resp);
            $notaFiscal->setCStatLote($std->cStat);
            $notaFiscal->setXMotivoLote($std->xMotivo);
            if ((string)$std->cStat === '103') {
                $notaFiscal->setNRec($std->infRec->nRec);
            }
            $this->notaFiscalEntityHandler->save($notaFiscal);
            $tentativa = 1;
            while (true) {
                $this->consultaRecibo($notaFiscal);
                if (!$notaFiscal->getCStat() || (int)$notaFiscal->getCStat() === -100) {
                    sleep(1);
                    if (++$tentativa === 4) break;
                } else {
                    break;
                }
            }
            return $notaFiscal;
        } catch (\Throwable $e) {
            $this->logger->error('enviaNFe - id: ' . $notaFiscal->getId());
            $this->logger->error($e->getMessage());
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
        $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->getDocumentoEmitente());
        $tools->model($notaFiscal->getTipoNotaFiscal() === 'NFE' ? '55' : '65');
        //consulta número de recibo
        //$numeroRecibo = número do recíbo do envio do lote
        $tpAmb = $notaFiscal->getAmbiente() === 'PROD' ? '1' : '2';
        $xmlResp = $tools->sefazConsultaChave($notaFiscal->getChaveAcesso(), $tpAmb);
        //transforma o xml de retorno em um stdClass
        $st = new Standardize();
        $std = $st->toStd($xmlResp);

        $notaFiscal->setCStatLote($std->cStat);
        $notaFiscal->setXMotivoLote($std->xMotivo);

        $this->addHistorico($notaFiscal, $std->cStat ?? -1, 'sefazConsultaChave', $xmlResp);

        if ($std->cStat === '104' || $std->cStat === '100') { //lote processado (tudo ok)
            $cStat = $std->protNFe->infProt->cStat;
            $notaFiscal->setCStat($cStat);
            $notaFiscal->setXMotivo($std->protNFe->infProt->xMotivo);
            if ($notaFiscal->getXmlNota() && $notaFiscal->getXMLDecoded()->getName() !== 'nfeProc') {
                try {
                    if (!isset($notaFiscal->getXMLDecoded()->infNFe->Signature) &&
                        !isset($notaFiscal->getXMLDecoded()->Signature)) {
                        $xmlAssinado = $tools->signNFe($notaFiscal->getXmlNota());
                        $notaFiscal->setXmlNota($xmlAssinado);
                    }
                    $r = Complements::toAuthorize($notaFiscal->getXmlNota(), $xmlResp);
                    $notaFiscal->setXmlNota($r);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                    $this->logger->error('Erro no Complements::toAuthorize para $notaFiscal->id = ' . $notaFiscal->getId());
                }
            }
            if (in_array($cStat, ['100', '302'])) { //DENEGADAS
                $notaFiscal->setProtocoloAutorizacao($std->protNFe->infProt->nProt);
                $notaFiscal->setDtProtocoloAutorizacao(DateTimeUtils::parseDateStr($std->protNFe->infProt->dhRecbto));
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
        if ($notaFiscal->getCStat() !== 100 && $notaFiscal->getCStat() !== 204) {
            throw new \RuntimeException('Nota Fiscal com status diferente de \'100\' ou de \'204\' não pode ser cancelada. (id: ' . $notaFiscal->getId() . ')');
        }

        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->getDocumentoEmitente());
        if ($notaFiscal->getDocumentoEmitente() !== $nfeConfigs['cnpj']) {
            throw new ViewException('Documento Emitente diferente do CNPJ configurado');
        }

        $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->getDocumentoEmitente());
        $tools->model($notaFiscal->getTipoNotaFiscal() === 'NFE' ? '55' : '65');

        $chaveNota = $notaFiscal->getChaveAcesso();
        $xJust = $notaFiscal->getMotivoCancelamento();
        $nProt = $notaFiscal->getProtocoloAutorizacao();
        $response = $tools->sefazCancela($chaveNota, $xJust, $nProt);

        $stdCl = new Standardize($response);
        $std = $stdCl->toStd();

        //verifique se o evento foi processado
        if ((string)$std->cStat !== '128') {
            $notaFiscal->setCStat($std->cStat);
            $notaFiscal->setXMotivo($std->retEvento->infEvento->xMotivo);
            /** @var NotaFiscal $notaFiscal */
            $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
        } else {
            $cStat = $std->retEvento->infEvento->cStat;
            if ($cStat == '101' || $cStat == '155' || $cStat == '135') {
                $xml = Complements::toAuthorize($tools->lastRequest, $response);

                $notaFiscal->setCStat($cStat);
                $notaFiscal->setXMotivo($std->retEvento->infEvento->xMotivo);
                /** @var NotaFiscal $notaFiscal */
                $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);

                $evento = new NotaFiscalEvento();
                $evento->setNotaFiscal($notaFiscal);
                $evento->setXml($xml);
                $evento->setDescEvento('CANCELAMENTO');
                $evento->setNSeqEvento(1);
                $evento->setTpEvento(110111);
                $this->notaFiscalEventoEntityHandler->save($evento);
            } else {
                $notaFiscal->setCStat($cStat);
                $notaFiscal->setXMotivo($std->retEvento->infEvento->xMotivo);
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
        $tools = $this->nfeUtils->getToolsByCNPJ($cartaCorrecao->getNotaFiscal()->getDocumentoEmitente());
        $tools->model($cartaCorrecao->getNotaFiscal()->getTipoNotaFiscal() === 'NFE' ? '55' : '65');

        $chave = $cartaCorrecao->getNotaFiscal()->getChaveAcesso();
        $nSeqEvento = $cartaCorrecao->getSeq();

        $response = $tools->sefazCCe($chave, $cartaCorrecao->getCartaCorrecao(), $nSeqEvento);

        $stdCl = new Standardize($response);
        $std = $stdCl->toStd();

        //verifique se o evento foi processado
        if ($std->cStat != 128) {
            $this->logger->error('Erro ao enviar carta de correção');
            $this->logger->error('$std->cStat != 128');
        } else {
            $cStat = $std->retEvento->infEvento->cStat;
            if ($cStat == '135' || $cStat == '136') {
                //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
                $xml = Complements::toAuthorize($tools->lastRequest, $response);
                $cartaCorrecao->setMsgRetorno($xml);
                $cartaCorrecao = $this->notaFiscalCartaCorrecaoEntityHandler->save($cartaCorrecao);
            } else {
                $this->logger->error('Erro ao enviar carta de correção');
                $this->logger->error('cStat: ' . $cStat);
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
            return $xml[0]->nfeResultMsg->retConsCad->infCons;
        } catch (\Exception $e) {
            throw new ViewException('Erro ao consultar o CNPJ');
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

            $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->getDocumentoDestinatario());

            $response = $tools->sefazManifesta($notaFiscal->getChaveAcesso(), $tpEvento, $xJust, $nSeqEvento);
            $st = new Standardize($response);

            if ($st->simpleXml()->cStat->__toString() === '128') {
                $notaFiscal->setManifestDest('210210 - Ciência da Operação');
            }
            $notaFiscal->setDtManifestDest(new \DateTime());

            $this->notaFiscalEntityHandler->save($notaFiscal);

        } catch (\Exception $e) {
            $this->logger->error('Erro ao processar XML');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao manifestar DFe (chave: ' . $notaFiscal->getChaveAcesso() . ')');
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
            $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->getDocumentoEmitente());
            $tools->model($notaFiscal->getTipoNotaFiscal() === 'NFE' ? '55' : '65');
            $response = $tools->sefazConsultaChave($notaFiscal->getChaveAcesso());

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
            $this->logger->error('Erro ao processar XML');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao consultaChaveDFe (chave: ' . $notaFiscal->getChaveAcesso() . ')');
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
                $this->logger->error('Erro ao fazer o parse do xml para NF (chave: ' . $nf->getChaveAcesso() . ')');
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
    public function inutilizaNumeracao(string $tipoNotaFiscal, int $serie, int $numero)
    {
        try {
            $tools = $this->nfeUtils->getToolsEmUso();
            $tools->model($tipoNotaFiscal === 'NFE' ? '55' : '65');
            $xJust = 'Erro de digitação dos números sequencias das notas';
            $response = $tools->sefazInutiliza($serie, $numero, $numero, $xJust, 1);
            $stdCl = new Standardize($response);
            return $stdCl->toArray();
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
            if (!$notaFiscal->getNRec()) {
                throw new ViewException('nRec N/D');
            }
            $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->getDocumentoEmitente());
            $tools->model($notaFiscal->getTipoNotaFiscal() === 'NFE' ? '55' : '65');
            $xmlResp = $tools->sefazConsultaRecibo($notaFiscal->getNRec());
            $std = (new Standardize($xmlResp))->toStd();
            $notaFiscal->setCStatLote($std->cStat);
            $this->addHistorico($notaFiscal, $std->cStat ?? -1, 'sefazConsultaRecibo', $xmlResp);
            $notaFiscal->setXMotivoLote($std->xMotivo);
            if ((int)$std->cStat === 104 || (int)$std->cStat === 100) { //lote processado (tudo ok)
                $cStat = $std->protNFe->infProt->cStat;
                $notaFiscal->setCStat($cStat);
                $notaFiscal->setXMotivo($std->protNFe->infProt->xMotivo);
                if ($notaFiscal->getXmlNota() && $notaFiscal->getXMLDecoded()->getName() !== 'nfeProc') {
                    try {
                        if (!isset($notaFiscal->getXMLDecoded()->infNFe->Signature) &&
                            !isset($notaFiscal->getXMLDecoded()->Signature)) {
                            $xmlAssinado = $tools->signNFe($notaFiscal->getXmlNota());
                            $notaFiscal->setXmlNota($xmlAssinado);
                        }
                        $r = Complements::toAuthorize($notaFiscal->getXmlNota(), $xmlResp);
                        $notaFiscal->setXmlNota($r);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                        $this->logger->error('Erro no Complements::toAuthorize para $notaFiscal->id = ' . $notaFiscal->getId());
                    }
                }
                if (in_array($cStat, ['100', '302'])) { //DENEGADAS
                    $notaFiscal->setProtocoloAutorizacao($std->protNFe->infProt->nProt);
                    $notaFiscal->setDtProtocoloAutorizacao(DateTimeUtils::parseDateStr($std->protNFe->infProt->dhRecbto));
                }
            }
            $this->notaFiscalEntityHandler->save($notaFiscal);
        } catch (\Throwable $e) {
            $this->logger->error('consultaRecibo - Id: ' . $notaFiscal->getId());
            $this->logger->error($e->getMessage());
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
