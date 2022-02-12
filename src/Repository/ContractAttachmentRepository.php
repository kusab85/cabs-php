<?php

namespace LegacyFighter\Cabs\Repository;

use Doctrine\ORM\EntityManagerInterface;
use LegacyFighter\Cabs\Entity\Contract;
use LegacyFighter\Cabs\Entity\ContractAttachment;

class ContractAttachmentRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(ContractAttachment $attachment): ContractAttachment
    {
        $this->em->persist($attachment);
        $this->em->flush();
        return $attachment;
    }

    public function deleteById(int $id): void
    {
        $this->em->remove($this->getOne($id));
        $this->em->flush();
    }

    public function getOne(int $id): ContractAttachment
    {
        return $this->em->find(ContractAttachment::class, $id) ?? throw new \InvalidArgumentException('Contract attachment not found');
    }

    /**
     * @return ContractAttachment[]
     */
    public function findByContractId(int $id): array
    {
        return $this->em->getRepository(ContractAttachment::class)->findBy(['contract' => $id]);
    }
}
