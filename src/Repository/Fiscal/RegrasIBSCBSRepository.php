<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Entity\Base\Municipio;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Business\Fiscal\NFeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\RegrasIBSCBS;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Repository para a entidade NCM.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class RegrasIBSCBSRepository extends FilterRepository
{

    private NFeUtils $nfeUtils;
    
    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return RegrasIBSCBS::class;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findBestRule(NotaFiscalItem $item): ?RegrasIBSCBS
    {
        $nf = $item->notaFiscal;

        $ncm = (int)($item->ncm ?? 0);
        $ncmGrupo = (int)substr(str_pad($ncm, 8, '0', STR_PAD_LEFT), 0, 4);

        $qb = $this->createQueryBuilder('r');

        $qb->where('r.regimeCrt = :regime_crt')
            ->andWhere('r.modelo = :modelo')
            ->andWhere('r.operacao = :operacao')
            ->andWhere('(r.ufOri = :uf_ori OR r.ufOri = :uf_ori_coringa)')
            ->andWhere('(r.ufDes = :uf_des OR r.ufDes = :uf_des_coringa)')
            ->andWhere('(r.codmunOri = :codmun_ori OR r.codmunOri = 0)')
            ->andWhere('(r.codmunDes = :codmun_des OR r.codmunDes = 0)')
            ->andWhere('(r.ncmGrupo = :ncm_grupo OR r.ncmGrupo = 9999)')
            ->andWhere('(r.ncm = :ncm OR r.ncm = 99999999)')
            ->andWhere('(r.cfop = :cfop OR r.cfop = 0)');

        $repoMunicipio = $this->getEntityManager()->getRepository(Municipio::class);
        /** @var Municipio $municipioOrigem */
        $municipioOrigem = $repoMunicipio->findByNomeAndUf($nf->cidadeEmitente, $nf->estadoEmitente);

        /** @var Municipio $municipioDestino */
        $municipioDestino = $repoMunicipio->findByNomeAndUf($nf->cidadeDestinatario, $nf->estadoDestinatario);

        $operacao = $nf->estadoEmitente === $nf->estadoDestinatario ? '1' : '2';


        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($nf->documentoEmitente);
        
        
        $qb->setParameters([
            'regime_crt' => $nfeConfigs['CRT'],
            'modelo' => $nf->tipoNotaFiscal === 'NFE' ? '55' : '65',
            'operacao' => $operacao,
            'uf_ori' => $nf->estadoEmitente,
            'uf_ori_coringa' => 'ZZ',
            'uf_des' => $nf->estadoDestinatario,
            'uf_des_coringa' => 'ZZ',
            'codmun_ori' => $municipioOrigem->municipioCodigo,
            'codmun_des' => $municipioDestino->municipioCodigo,
            'ncm_grupo' => $ncmGrupo,
            'ncm' => $ncm,
            'cfop' => $item->cfop,
        ]);

        // A mágica — ordena pela regra mais específica possível
        $qb->orderBy('(r.ncm = :ncm)', 'DESC')
            ->addOrderBy('(r.ncmGrupo = :ncm_grupo)', 'DESC')
            ->addOrderBy('(r.ufDes = :uf_des)', 'DESC')
            ->addOrderBy('(r.codmunDes = :codmun_des)', 'DESC')
            ->addOrderBy('r.prioridade', 'ASC');

        return $qb->getQuery()->getOneOrNullResult();
    }


}
