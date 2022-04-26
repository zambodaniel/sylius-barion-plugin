<?php

declare(strict_types=1);

namespace ZamboDaniel\SyliusBarionPlugin\Payum;

use Sylius\Component\Core\Model\PaymentInterface;

final class BarionApi
{
    /** @var string */
    private $posKey;

    /** @var string */
    private $payee;

    /** @var string */
    private $env;

    public function __construct(string $posKey, string $payee, string $env)
    {
        $this->posKey = $posKey;
        $this->payee = $payee;
        $this->env = $env;
    }


    public function getPosKey(): string
    {
        return $this->posKey;
    }

    public function getPayee(): string
    {
        return $this->payee;
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    private function getBarionClient()
    {
        return new \BarionClient($this->posKey, 2, $this->env);
    }

    public function preparePayment(PaymentInterface $payment, float $total, int $divisor, string $redirectUrl, string $callbackUrl): \PreparePaymentResponseModel
    {
        $transaction = new \PaymentTransactionModel();
        $transaction->POSTransactionId = $payment->getId();
        $transaction->Payee = $this->payee;
        $transaction->Total = intval(round($total / $divisor));

        foreach ($payment->getOrder()->getItems() as $orderItem) {
            $item = new \ItemModel();
            $item->Name = $orderItem->getVariantName() ?? $orderItem->getProductName();
            $item->Description = $orderItem->getProduct()->getShortDescription() ?? $item->Name;
            $item->Quantity = $orderItem->getQuantity();
            $item->Unit = 'db';
            $item->UnitPrice = intval($orderItem->getUnitPrice() / $divisor);
            $item->ItemTotal = intval($orderItem->getTotal() / $divisor);
            $item->SKU = $orderItem->getVariant()->getCode();
            $transaction->AddItem($item);
        }

        $paymentRequest = new \PreparePaymentRequestModel();
        $paymentRequest->OrderNumber = $payment->getOrder()->getNumber();
        $paymentRequest->PaymentRequestId = $payment->getId() . '-' . time();
        $paymentRequest->PayerHint = $payment->getOrder()->getCustomer()->getEmail();
        $paymentRequest->Locale = str_replace('_', '-', $payment->getOrder()->getLocaleCode());
        $paymentRequest->Currency = $payment->getOrder()->getCurrencyCode();

        $shipmentAddress = $payment->getOrder()->getShippingAddress();
        if ($shipmentAddress) {
            $paymentRequest->ShippingAddress =
                $shipmentAddress->getPostcode()
                . ' ' . $shipmentAddress->getCity()
                . ' ' . $shipmentAddress->getStreet();
        }

        $paymentRequest->RedirectUrl = $redirectUrl;
        $paymentRequest->CallbackUrl = $callbackUrl;

        $paymentRequest->AddTransaction($transaction);

        return $this->getBarionClient()->PreparePayment($paymentRequest);
    }

    public function getPaymentState(string $paymentId): \PaymentStateResponseModel
    {
        return $this->getBarionClient()->GetPaymentState($paymentId);

    }

}
