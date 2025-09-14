<?php

namespace PalPalych\Payments\Classes\Infrastructure\Mapper;

use ReflectionProperty;
use PalPalych\Payments\Models\PaymentMethod as PaymentMethodModel;
use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod as PaymentMethodEntity;

class PaymentMethodMapper
{
    /**
     * Maps an Eloquent PaymentMethod model to a domain PaymentMethod entity.
     *
     * @param PaymentMethodModel $model
     * @return PaymentMethodEntity
     * @throws \ReflectionException
     */
    public static function toEntity(PaymentMethodModel $model): PaymentMethodEntity
    {
        $entity = new PaymentMethodEntity();

        // Use reflection to set the private ID, preserving entity immutability.
        $reflectionId = new ReflectionProperty($entity, 'id');
        $reflectionId->setAccessible(true);
        $reflectionId->setValue($entity, $model->id);

        $entity
            ->setUserId($model->user_id)
            ->setIdempotenceKey($model->idempotence_key)
            ->setGatewayRequest(is_string($model->gateway_request) ? $model->gateway_request : json_encode($model->gateway_request))
            ->setGatewayResponse(is_string($model->gateway_response) ? $model->gateway_response : json_encode($model->gateway_response))
            ->setGatewayId($model->gateway_id)
            ->setStatus($model->status)
            ->setCardType($model->card_type)
            ->setLast4($model->last4)
            ->setExpiryYear($model->expiry_year)
            ->setExpiryMonth($model->expiry_month)
            ->setAcceptedAt($model->accepted_at)
            ->setCreatedAt($model->created_at)
            ->setUpdatedAt($model->updated_at);

        return $entity;
    }

    /**
     * Maps a domain PaymentMethod entity to an Eloquent PaymentMethod model.
     *
     * @param PaymentMethodEntity $entity
     * @param PaymentMethodModel $model
     * @return void
     */
    public static function toModel(PaymentMethodEntity $entity, PaymentMethodModel $model): void
    {
        $model->user_id = $entity->getUserId();
        $model->idempotence_key = $entity->getIdempotenceKey();
        $model->gateway_request = $entity->getGatewayRequest() ? json_decode($entity->getGatewayRequest(), true) : null;
        $model->gateway_response = $entity->getGatewayResponse() ? json_decode($entity->getGatewayResponse(), true) : null;
        $model->gateway_id = $entity->getGatewayId();
        $model->status = $entity->getStatus();
        $model->card_type = $entity->getCardType();
        $model->last4 = $entity->getLast4();
        $model->expiry_year = $entity->getExpiryYear();
        $model->expiry_month = $entity->getExpiryMonth();
        $model->accepted_at = $entity->getAcceptedAt();
    }
}
