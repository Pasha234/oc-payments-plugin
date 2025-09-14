## Плагин "Payments" для OctoberCMS

Этот плагин предоставляет интеграцию с платежным шлюзом [ЮKassa](https://yookassa.ru/) для обработки платежей, управления сохраненными способами оплаты и обработки веб-хуков в OctoberCMS.

### Основные возможности

*   Создание разовых платежей.
*   Создание платежей с использованием сохраненных банковских карт.
*   Управление способами оплаты (добавление/удаление).
*   Автоматическая проверка статуса платежа после возврата со страницы оплаты.
*   Обработка веб-хуков от ЮKassa для обновления статусов платежей и карт.
*   Интеграция с моделью `RainLab.User`.

### Установка и настройка

1.  **Установка**: Установите плагин стандартным для OctoberCMS способом. Убедитесь, что плагин `RainLab.User` также установлен.

2.  **Настройка**:
    *   Перейдите в "Настройки" -> "Платежи".
    *   Введите ваш `shop_id` и `secret_key` от ЮKassa. Эти данные можно найти в личном кабинете ЮKassa.
    *   Сохраните настройки.

3.  **Настройка веб-хуков в ЮKassa**:
    *   В личном кабинете ЮKassa перейдите в раздел "Интеграция" -> "HTTP-уведомления".
    *   В качестве URL для уведомлений укажите: `https://ваш-сайт.ru/yookassa-webhook`.
    *   Выберите все события, связанные с платежами (`payment.*`) и способами оплаты (`payment_method.*`).

### Использование

Плагин предоставляет несколько компонентов для использования на страницах вашего сайта.

#### 1. Компонент `PaymentCheck`

Этот компонент необходимо разместить на страницах, куда пользователь возвращается после оплаты или привязки карты. Он автоматически проверяет и обновляет статус платежа или способа оплаты.

**Как использовать:**
Добавьте компонент на вашу страницу:

```twig
[PaymentCheck]
```

Компонент будет активирован, если в URL-адресе есть параметры `payment_id` или `payment_method_id`.

#### 2. Компонент `paymentMethods`

Отображает список сохраненных способов оплаты пользователя и позволяет добавлять новые или удалять существующие.

**Пример использования на странице профиля:**

```twig
[paymentMethods]
---
<div class="container">
    <h3>Мои способы оплаты</h3>
    {% component 'paymentMethods' %}
</div>
```

Компонент имеет встроенную логику для добавления и удаления карт через AJAX-запросы.

#### 3. Реализация `PayableInterface` (Оплачиваемая сущность)

Чтобы система могла работать с вашими товарами, услугами или любыми другими сущностями, за которые можно заплатить, ваша Eloquent-модель должна реализовывать интерфейс `PayableInterface`.

Это "контракт", который сообщает платежной системе, как получить необходимую информацию для создания платежа.

**Шаг 1: Создайте вашу модель и миграцию**

Например, создадим модель `Diploma` (Диплом), которую можно купить.

**Шаг 2: Реализуйте интерфейс**

В вашей модели (например, `plugins/myauthor/myplugin/models/Diploma.php`) добавьте `implements PayableInterface` и реализуйте его методы.

```php
use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;
use October\Rain\Database\Model;

class Diploma extends Model implements PayableInterface
{
    // ... ваш код модели

    public function getPayableId(): string
    {
        return (string) $this->id;
    }

    public function getPayableType(): string
    {
        return self::class;
    }

    /**
     * Возвращает сумму к оплате в копейках.
     */
    public function getPayableAmount(): int
    {
        return 15000; // 150.00 рублей
    }

    /**
     * Возвращает описание, которое увидит пользователь в ЮKassa.
     */
    public function getPayableDescription(): string
    {
        return "Покупка диплома №{$this->id}";
    }

    /**
     * Этот метод будет вызван после успешной оплаты.
     * Здесь вы можете обновить статус вашей модели.
     */
    public function markAsPaid(): void
    {
        $this->is_purchased = true;
        $this->save();
    }

    public function getReceiptItems(): ReceiptItems
    {
        $receiptItems = new ReceiptItems();
        $receiptItems->addItem(new ReceiptItem(
            'Диплом',
            new ReceiptItemAmount(
                $this->getPayableAmount(),
                ReceiptItemCurrency::rub,
            ),
            VatCode::without_vat,
            1
        ));
        return $receiptItems;
    }

}
```

**Шаг 3: Используйте вашу модель при создании платежа**

Теперь вы можете использовать эту модель для инициации платежа, как показано в сценариях ниже. Платежная система будет автоматически вызывать методы `getPayableAmount()` и `getPayableDescription()` у вашего объекта `$diploma`.

#### 4. Программное создание платежей

Для инициации платежа необходимо использовать соответствующие UseCase'ы из вашего кода (например, в компоненте или контроллере).

##### Сценарий 1: Оплата без сохраненной карты

В этом случае пользователь будет перенаправлен на сайт ЮKassa для выбора способа оплаты.

```php
use PalPalych\Payments\Classes\Application\Usecase\Payment\CreatePaymentUseCase;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentRequest;

// $payable - это ваша модель, которая реализует PayableInterface
// $user - текущий пользователь

/** @var CreatePaymentUseCase $useCase */
$useCase = app(CreatePaymentUseCase::class);

$request = new CreatePaymentRequest(
    userId: $user->id,
    payableId: $payable->id,
    payableType: get_class($payable),
    success_url: 'https://ваш-сайт.ru/payment/success', // URL для возврата после оплаты
    client_email: $user->email,
);

$response = $useCase($request);

return \Redirect::to($response->confirmation_url); // Перенаправляем пользователя на оплату
```

##### Сценарий 2: Оплата с использованием сохраненной карты

Этот сценарий используется для рекуррентных платежей или быстрой оплаты без перехода на сайт платежной системы.

```php
use PalPalych\Payments\Classes\Application\Usecase\Payment\CreatePaymentWithPaymentMethodUseCase;
use PalPalych\Payments\Classes\Application\Dto\Request\CreatePaymentWithPaymentMethodRequest;

// $payable - ваша модель, реализующая PayableInterface
// $user - текущий пользователь
// $paymentMethodId - ID сохраненного способа оплаты

/** @var CreatePaymentWithPaymentMethodUseCase $useCase */
$useCase = app(CreatePaymentWithPaymentMethodUseCase::class);

$request = new CreatePaymentWithPaymentMethodRequest(
    userId: $user->id,
    payableId: $payable->id,
    payableType: get_class($payable),
    paymentMethodId: $paymentMethodId,
    client_email: $user->email,
);

$response = $useCase($request); // Платеж будет выполнен автоматически

// Здесь можно обработать результат и показать пользователю сообщение
```

