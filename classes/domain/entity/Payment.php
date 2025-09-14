<?php

namespace PalPalych\Payments\Classes\Domain\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;

class Payment
{
    private ?int $id = null;
    private int $user_id;
    private ?int $total = null;
    private ?string $idempotence_key = null;
    private ?string $gateway_request = null;
    private ?string $gateway_response = null;
    private ?string $gateway_id = null;
    private ?int $payment_method_id = null;
    private PaymentStatus $status = PaymentStatus::pending;
    private ?int $payable_id = null;
    private ?string $payable_type = null;
    private ?DateTimeInterface $paid_at = null;
    private ?DateTimeInterface $created_at = null;
    private ?DateTimeInterface $updated_at = null;
    private ?DateTimeInterface $deleted_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(?int $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getIdempotenceKey(): ?string
    {
        return $this->idempotence_key;
    }

    public function setIdempotenceKey(?string $idempotence_key): self
    {
        $this->idempotence_key = $idempotence_key;
        return $this;
    }

    public function getGatewayRequest(): ?string
    {
        return $this->gateway_request;
    }

    public function setGatewayRequest(?string $gateway_request): self
    {
        $this->gateway_request = $gateway_request;
        return $this;
    }

    public function getGatewayResponse(): ?string
    {
        return $this->gateway_response;
    }

    public function setGatewayResponse(?string $gateway_response): self
    {
        $this->gateway_response = $gateway_response;
        return $this;
    }

    public function getGatewayId(): ?string
    {
        return $this->gateway_id;
    }

    public function setGatewayId(?string $gateway_id): self
    {
        $this->gateway_id = $gateway_id;
        return $this;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPaymentMethodId(): ?int
    {
        return $this->payment_method_id;
    }

    public function setPaymentMethodId(?int $payment_method_id): self
    {
        $this->payment_method_id = $payment_method_id;
        return $this;
    }

    public function getPayableId(): ?int
    {
        return $this->payable_id;
    }

    public function setPayableId(?int $payable_id): self
    {
        $this->payable_id = $payable_id;
        return $this;
    }

    public function getPayableType(): ?string
    {
        return $this->payable_type;
    }

    public function setPayableType(?string $payable_type): self
    {
        $this->payable_type = $payable_type;
        return $this;
    }

    public function getPaidAt(): ?DateTimeInterface
    {
        return $this->paid_at;
    }

    public function setPaidAt(?DateTimeInterface $paid_at): self
    {
        $this->paid_at = $paid_at;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?DateTimeInterface $deleted_at): self
    {
        $this->deleted_at = $deleted_at;
        return $this;
    }

    public function markAsPaid(): void
    {
        if ($this->status !== PaymentStatus::pending) {
            throw new \LogicException('Payment cannot be completed from its current state.');
        }

        $this->status = PaymentStatus::success;
        $this->paid_at = new DateTimeImmutable();
    }

    public function markAsFailed(): void
    {
        if ($this->status !== PaymentStatus::pending) {
            throw new \LogicException('Payment cannot be failed from its current state.');
        }
        $this->status = PaymentStatus::failed;
    }

    public function markAsCanceled(): void
    {
        if ($this->status !== PaymentStatus::pending) {
            throw new \LogicException('Payment cannot be failed from its current state.');
        }
        $this->status = PaymentStatus::canceled;
    }
}
