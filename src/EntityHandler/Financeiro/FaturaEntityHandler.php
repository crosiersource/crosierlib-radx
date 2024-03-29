<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class FaturaEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class FaturaEntityHandler extends EntityHandler
{

    public CadeiaEntityHandler $cadeiaEntityHandler;

    public function getEntityClass()
    {
        return Fatura::class;
    }

    public function __construct(ManagerRegistry       $doctrine,
                                Security              $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness        $syslog,
                                CadeiaEntityHandler   $cadeiaEntityHandler)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->cadeiaEntityHandler = $cadeiaEntityHandler;
    }


    /**
     * @param Fatura $fatura
     * @return mixed|void
     */
    public function beforeSave($fatura)
    {
        if (!$fatura->dtFatura) {
            $fatura->dtFatura = new \DateTime();
        }
        $fatura->descricao = substr($fatura->descricao, 0, 500);

        $fatura->quitada = $fatura->getSaldo() <= 0.0;
    }


    public function estornar(Fatura $fatura): void
    {
        $conn = $this->getDoctrine()->getConnection();
        try {
            $conn->beginTransaction();
            foreach ($fatura->movimentacoes as $movimentacao) {
                $movimentacao->status = 'ESTORNADA';
                $conn->update('fin_movimentacao', ['status' => 'ESTORNADA'], ['id' => $movimentacao->getId()]);
            }
            $fatura->cancelada = true;
            $fatura->jsonData['estornada_em'] = DateTimeUtils::getSQLFormatted();
            $this->save($fatura);
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
        }
    }


}
