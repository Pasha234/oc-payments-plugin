<?php

namespace PalPalych\Payments\Classes\Infrastructure\Mapper;

use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;
use ReflectionProperty;
use PalPalych\Payments\Models\Payment as PaymentModel;
use PalPalych\Payments\Classes\Domain\Entity\Payment as PaymentEntity;
use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus as DomainPaymentStatus;

class PaymentMapper
{
    /**
     * Maps an Eloquent Payment model to a domain Payment entity.
     *
     * @param PaymentModel $model
     * @return PaymentEntity
     * @throws \ReflectionException
     */
    public static function toEntity(PaymentModel $model): PaymentEntity
    {
        $entity = new PaymentEntity();

        // Use reflection to set the private ID, preserving entity immutability.
        $reflectionId = new ReflectionProperty($entity, 'id');
        $reflectionId->setAccessible(true);
        $reflectionId->setValue($entity, $model->id);

        $entity
            ->setUserId($model->user_id)
            ->setTotal($model->total)
            ->setIdempotenceKey($model->idempotence_key)
            // Ensure gateway data is stored as a JSON string in the entity.
            ->setGatewayRequest(is_string($model->gateway_request) ? $model->gateway_request : json_encode($model->gateway_request))
            ->setGatewayResponse(is_string($model->gateway_response) ? $model->gateway_response : json_encode($model->gateway_response))
            ->setGatewayId($model->gateway_id)
            ->setPaymentMethodId($model->payment_method_id)
            // Map from the model's enum to the domain's enum.
            ->setStatus(DomainPaymentStatus::from($model->status->value))
            ->setPayableId($model->payable_id)
            ->setPayableType($model->payable_type)
            ->setPaidAt($model->paid_at)
            ->setCreatedAt($model->created_at)
            ->setUpdatedAt($model->updated_at);

        return $entity;
    }

    /**
     * Maps a domain Payment entity to an Eloquent Payment model.
     *
     * @param PaymentEntity $entity
     * @param PaymentModel $model
     * @return void
     */
    public static function toModel(PaymentEntity $entity, PaymentModel $model): void
    {
        $model->user_id = $entity->getUserId();
        $model->total = $entity->getTotal();
        $model->idempotence_key = $entity->getIdempotenceKey();
        // Decode JSON string from entity for the model.
        $model->gateway_request = $entity->getGatewayRequest() ? json_decode($entity->getGatewayRequest(), true) : null;
        $model->gateway_response = $entity->getGatewayResponse() ? json_decode($entity->getGatewayResponse(), true) : null;
        $model->gateway_id = $entity->getGatewayId();
        $model->payment_method_id = $entity->getPaymentMethodId();
        // Map from the domain's enum to the model's enum.
        $model->status = PaymentStatus::from($entity->getStatus()->value);
        $model->payable_id = $entity->getPayableId();
        $model->payable_type = $entity->getPayableType();
        $model->paid_at = $entity->getPaidAt();
    }
}
