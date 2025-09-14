<?php

namespace PalPalych\Payments\Classes\Domain\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;

class PaymentMethod
{
    private ?int $id = null;
    private int $user_id;
    private ?string $title = null;
    private ?string $idempotence_key = null;
    private ?string $gateway_request = null;
    private ?string $gateway_response = null;
    private ?string $gateway_id = null;
    private ?string $card_type = null;
    private ?string $last4 = null;
    private ?string $expiry_year = null;
    private ?string $expiry_month = null;
    private PaymentMethodStatus $status = PaymentMethodStatus::pending;
    private ?DateTimeInterface $accepted_at = null;
    private ?DateTimeInterface $created_at = null;
    private ?DateTimeInterface $updated_at = null;

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
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

    public function getCardType(): ?string
    {
        return $this->card_type;
    }

    public function setCardType(?string $card_type): self
    {
        $this->card_type = $card_type;
        return $this;
    }

    public function getLast4(): ?string
    {
        return $this->last4;
    }

    public function setLast4(?string $last4): self
    {
        $this->last4 = $last4;
        return $this;
    }

    public function getExpiryYear(): ?string
    {
        return $this->expiry_year;
    }

    public function setExpiryYear(?string $expiry_year): self
    {
        $this->expiry_year = $expiry_year;
        return $this;
    }

    public function getExpiryMonth(): ?string
    {
        return $this->expiry_month;
    }

    public function setExpiryMonth(?string $expiry_month): self
    {
        $this->expiry_month = $expiry_month;
        return $this;
    }

    public function getStatus(): PaymentMethodStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentMethodStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getAcceptedAt(): ?DateTimeInterface
    {
        return $this->accepted_at;
    }

    public function setAcceptedAt(?DateTimeInterface $accepted_at): self
    {
        $this->accepted_at = $accepted_at;
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

    public function markAsAccepted(): void
    {
        if ($this->status !== PaymentMethodStatus::pending) {
            throw new \LogicException('Payment method cannot be completed from its current state.');
        }

        $this->status = PaymentMethodStatus::success;
        $this->accepted_at = new DateTimeImmutable();
    }

    public function markAsFailed(): void
    {
        if ($this->status !== PaymentMethodStatus::pending) {
            throw new \LogicException('Payment method cannot be failed from its current state.');
        }
        $this->status = PaymentMethodStatus::failed;
    }

    public function markAsCanceled(): void
    {
        if ($this->status !== PaymentMethodStatus::pending) {
            throw new \LogicException('Payment method cannot be failed from its current state.');
        }
        $this->status = PaymentMethodStatus::canceled;
    }
}
