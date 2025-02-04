<?php

namespace LegacyFighter\Cabs\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use LegacyFighter\Cabs\Common\BaseEntity;
use Symfony\Component\Uid\Uuid;

#[Entity]
class ContractAttachment extends BaseEntity
{
    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_ACCEPTED_BY_ONE_SIDE = 'accepted-by-one-side';
    public const STATUS_ACCEPTED_BY_BOTH_SIDES = 'accepted-by-both-side';
    public const STATUS_REJECTED = 'rejected';

    #[Column(type:'uuid', nullable: false)]
    private Uuid $contractAttachmentNo;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rejectedAt = null;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $changeDate = null;

    #[Column]
    private string $status = self::STATUS_PROPOSED;

    #[ManyToOne(targetEntity: Contract::class)]
    private Contract $contract;

    public function __construct()
    {
        $this->contractAttachmentNo = Uuid::v4();
        $this->creationDate = new \DateTimeImmutable();
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeImmutable $acceptedAt): void
    {
        $this->acceptedAt = $acceptedAt;
    }

    public function getRejectedAt(): ?\DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function setRejectedAt(?\DateTimeImmutable $rejectedAt): void
    {
        $this->rejectedAt = $rejectedAt;
    }

    public function getChangeDate(): ?\DateTimeImmutable
    {
        return $this->changeDate;
    }

    public function setChangeDate(?\DateTimeImmutable $changeDate): void
    {
        $this->changeDate = $changeDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if(!in_array($status, [self::STATUS_REJECTED, self::STATUS_PROPOSED, self::STATUS_ACCEPTED_BY_BOTH_SIDES, self::STATUS_ACCEPTED_BY_ONE_SIDE], true)) {
            throw new \InvalidArgumentException('Invalid status provided');
        }
        $this->status = $status;
    }

    public function getContract(): Contract
    {
        return $this->contract;
    }

    public function setContract(Contract $contract): void
    {
        $this->contract = $contract;
    }

    public function getContractAttachmentNo(): Uuid
    {
        return $this->contractAttachmentNo;
    }
}
