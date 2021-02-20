<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Movimentacao;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class MovimentacaoEntityHandler extends EntityHandler
{

    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness $syslog)
    {
        $syslog->setApp('radx')->setComponent(self::class);
        parent::__construct($doctrine, $security, $parameterBag, $syslog);
    }


    /**
     * @required
     * @param SyslogBusiness $syslog
     */
    public function setSyslog(SyslogBusiness $syslog): void
    {
        $this->syslog = $syslog->setApp('radx')->setComponent(self::class);
    }

    public function getEntityClass(): string
    {
        return Movimentacao::class;
    }

    /**
     * @param Movimentacao $movimentacao
     * @throws ViewException
     */
    public function integrar(Movimentacao $movimentacao)
    {
        if ($movimentacao->status === 'ABERTO') {
            $this->syslog->info('Integrando Movimentacao ao estoque (id = "' . $movimentacao->getId() . '")');

            $conn = $this->doctrine->getConnection();
            $conn->beginTransaction();

            try {
                $selectSQL = 'SELECT * FROM est_produto_saldo WHERE produto_id = :produtoId AND unidade_id = :unidadeId';
                $updateSQL = 'UPDATE est_produto_saldo SET qtde = (qtde + :qtde) WHERE produto_id = :produtoId AND unidade_id = :unidadeId';
                $insertSQL = 'INSERT INTO est_produto_saldo(produto_id, qtde, unidade_id, inserted, updated, version, estabelecimento_id, user_inserted_id, user_updated_id) ' .
                    ' VALUES(:produtoId, :qtde, :unidadeId, now(), now(), 0, 1, 1, 1)';
                foreach ($movimentacao->itens as $item) {
                    if ($conn->fetchAll($selectSQL, [
                        'produtoId' => $item->produto->getId(),
                        'unidadeId' => $item->unidade->getId()])) {
                        $conn->executeUpdate($updateSQL, [
                            'qtde' => $item->qtde,
                            'produtoId' => $item->produto->getId(),
                            'unidadeId' => $item->unidade->getId()
                        ]);
                    } else {
                        $conn->executeUpdate($insertSQL, [
                            'qtde' => $item->qtde,
                            'produtoId' => $item->produto->getId(),
                            'unidadeId' => $item->unidade->getId(),
                        ]);
                    }
                }
                $movimentacao->status = 'INTEGRADO';
                $movimentacao->dtIntegracao = new \DateTime();
                $this->save($movimentacao);
                $conn->commit();
                $this->syslog->info('Movimentacao integrada ao estoque com sucesso (id = "' . $movimentacao->getId() . '")');
            } catch (\Exception $e) {
                $this->syslog->info('Erro ao integrar movimentacao ao estoque (id = "' . $movimentacao->getId() . '")', $e->getTraceAsString());
                try {
                    $conn->rollBack();
                } catch (ConnectionException $e) {
                    $this->syslog->info('Erro no rollback (id = "' . $movimentacao->getId() . '")', $e->getTraceAsString());
                }
            }
        } else {
            throw new ViewException('Status difere de "ABERTO"');
        }
    }
}