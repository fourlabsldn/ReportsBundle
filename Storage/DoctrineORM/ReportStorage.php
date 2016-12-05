<?php

namespace FL\ReportsBundle\Storage\DoctrineORM;

use Doctrine\ORM\EntityManagerInterface;
use FL\ReportsBundle\Model\ReportInterface;
use FL\ReportsBundle\Storage\ReportStorageInterface;

class ReportStorage implements ReportStorageInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $reportClass;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $reportClass
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        string $reportClass
    ) {
        $this->entityManager = $entityManager;
        $this->reportClass = $reportClass;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): array
    {
        return $this->entityManager->getRepository($this->reportClass)->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        return $this->entityManager->getRepository($this->reportClass)->findOneBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(ReportInterface $report)
    {
        $this->entityManager->persist($report);
        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function remove(ReportInterface $report)
    {
        $this->entityManager->remove($report);
        $this->entityManager->flush();
    }
}
