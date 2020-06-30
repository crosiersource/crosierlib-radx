<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\BandeiraCartao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository para a entidade BandeiraCartao.
 *
 * @author Carlos Eduardo Pauluk
 */
class BandeiraCartaoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return BandeiraCartao::class;
    }

    public function handleFrombyFilters(QueryBuilder $qb)
    {
        return $qb->from($this->getEntityClass(), 'e')
            ->join(Modo::class, 'm', 'WITH', 'e.modo = m');
    }

    /**
     * @param $str
     * @param Modo $modo
     * @return BandeiraCartao|mixed
     * @throws ViewException
     */
    public function findByLabelsAndModo($str, Modo $modo)
    {
        try {
            $str = strtoupper($str);
            $ql = "SELECT bc FROM CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\BandeiraCartao bc WHERE bc.modo = :modo AND (bc.descricao LIKE :str OR bc.labels LIKE :str)";
            $qry = $this->getEntityManager()->createQuery($ql);
            $qry->setParameter('modo', $modo);
            $qry->setParameter('str', '%' . $str . '%');
            $r = $qry->getSingleResult();// Senão encontrar, então usa a comparação inversa
            if (!$r) {

                $todas = $this->findAll();

                /** @var BandeiraCartao $bc */
                foreach ($todas as $bc) {
                    if ($bc->getModo() === $modo) {

                        $labels = explode("\n", $bc->getLabels());

                        foreach ($labels as $label) {
                            if (stripos($label, $str)) {
                                return $bc;
                            }
                        }
                    }
                }
            } else {
                return $r;
            }
        } catch (\Exception $e) {
            throw new ViewException('Bandeira não encontrada para "' . $str . '"');
        }
    }

    /**
     * @param array $sel
     * @return false|string|void
     */
    public function getSelect2js($sel = [])
    {
        $rs = $this->getEntityManager()->getConnection()->fetchAll('SELECT * FROM fin_bandeira_cartao ORDER BY descricao');
        if (!is_array($sel)) {
            $sel = [$sel];
        }
        foreach ($rs as $e) {
            $r[] = [
                'id' => $e['id'],
                'text' => $e['descricao'],
                'selected' => in_array($e['id'], $sel) ? 'selected' : ''
            ];
        }
        return json_encode($r);
    }
}
