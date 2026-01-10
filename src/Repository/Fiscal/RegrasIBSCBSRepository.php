<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Entity\Base\Municipio;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Business\Fiscal\NFeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\RegrasIBSCBS;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Repository para a entidade NCM.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class RegrasIBSCBSRepository extends FilterRepository
{

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
    public function findBestRule(
        NotaFiscalItem $item,
        array $nfeConfigs
    ): ?RegrasIBSCBS
    {
        $nf = $item->notaFiscal;

        $ncm = (int) ($item->ncm ?? 0);
        $ncmGrupo = (int) substr(str_pad((string)$ncm, 8, '0', STR_PAD_LEFT), 0, 4);

        $operacao = $nf->estadoEmitente === $nf->estadoDestinatario ? 1 : 2;
        $modelo   = $nf->tipoNotaFiscal === 'NFE' ? 55 : 65;

        $repoMunicipio = $this->getEntityManager()->getRepository(Municipio::class);

        /** @var Municipio|null $municipioOrigem */
        $municipioOrigem = $repoMunicipio->findByNomeAndUf($nf->cidadeEmitente, $nf->estadoEmitente);

        /** @var Municipio|null $municipioDestino */
        $municipioDestino = $repoMunicipio->findByNomeAndUf($nf->cidadeDestinatario, $nf->estadoDestinatario);

        $codMunOri = (int) ($municipioOrigem?->municipioCodigo ?? 0);
        $codMunDes = (int) ($municipioDestino?->municipioCodigo ?? 0);

        $cfopItem = (int) ($item->cfop ?? 9999);

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
            ->andWhere('(r.cfop = :cfop OR r.cfop = 9999)');

        $qb->setParameters([
            'regime_crt'     => (int) ($nfeConfigs['CRT'] ?? 0),
            'modelo'         => $modelo,
            'operacao'       => $operacao,
            'uf_ori'         => (string) $nf->estadoEmitente,
            'uf_ori_coringa' => 'ZZ',
            'uf_des'         => (string) $nf->estadoDestinatario,
            'uf_des_coringa' => 'ZZ',
            'codmun_ori'     => $codMunOri,
            'codmun_des'     => $codMunDes,
            'ncm_grupo'      => $ncmGrupo,
            'ncm'            => $ncm,
            'cfop'           => $cfopItem,
        ]);

        $qb->addSelect("
        CASE WHEN r.ncm = :ncm THEN 1 ELSE 0 END AS HIDDEN ord_ncm,
        CASE WHEN r.ncmGrupo = :ncm_grupo THEN 1 ELSE 0 END AS HIDDEN ord_ncm_grupo,
        CASE WHEN r.ufDes = :uf_des THEN 1 ELSE 0 END AS HIDDEN ord_uf_des,
        CASE WHEN r.codmunDes = :codmun_des THEN 1 ELSE 0 END AS HIDDEN ord_codmun_des,
        CASE WHEN r.ufOri = :uf_ori THEN 1 ELSE 0 END AS HIDDEN ord_uf_ori,
        CASE WHEN r.codmunOri = :codmun_ori THEN 1 ELSE 0 END AS HIDDEN ord_codmun_ori,
        CASE WHEN r.cfop = :cfop THEN 1 ELSE 0 END AS HIDDEN ord_cfop
    ");

        $qb->orderBy('ord_ncm', 'DESC')
            ->addOrderBy('ord_ncm_grupo', 'DESC')
            ->addOrderBy('ord_uf_des', 'DESC')
            ->addOrderBy('ord_codmun_des', 'DESC')
            ->addOrderBy('ord_uf_ori', 'DESC')
            ->addOrderBy('ord_codmun_ori', 'DESC')
            ->addOrderBy('ord_cfop', 'DESC')
            ->addOrderBy('r.prioridade', 'ASC');

        return $qb->getQuery()->getOneOrNullResult();
    }


}
