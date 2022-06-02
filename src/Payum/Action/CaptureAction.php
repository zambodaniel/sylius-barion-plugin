<?php

declare(strict_types=1);

namespace ZamboDaniel\SyliusBarionPlugin\Payum\Action;

use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\GetCurrency;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Security\TokenInterface;
use ZamboDaniel\SyliusBarionPlugin\Payum\BarionApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Payum\Core\Request\Capture;


final class CaptureAction implements ActionInterface, ApiAwareInterface, GenericTokenFactoryAwareInterface, GatewayAwareInterface
{

    use GatewayAwareTrait, GenericTokenFactoryAwareTrait, BarionApiTrait;

    /** @var BarionApi */
    private $api;

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var TokenInterface $token */
        $token = $request->getToken();

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();
        $model = $payment->getDetails();

        if (empty($model['status'])) {
            try {
                $notifyToken = $this->tokenFactory->createNotifyToken(
                    $token->getGatewayName(),
                    $token->getDetails()
                );
                $model['notifyToken'] = $notifyToken->getHash();
                $model['notifyURL'] = $notifyToken->getTargetUrl();

                $currency = new GetCurrency($payment->getCurrencyCode());
                $this->gateway->execute($currency);
                $divisor = pow(10, $currency->exp);

                $response = $this->api->preparePayment(
                    $payment,
                    $payment->getAmount(),
                    $divisor,
                    $request->getToken()->getTargetUrl(),
                    $model['notifyURL']
                );
            } catch (\Exception $exception) {
                $model['status'] = GetHumanStatus::STATUS_FAILED;
            }
            if (isset($response) && $response->RequestSuccessful && 'Prepared' == $response->Status && $response->PaymentId) {
                $model['status'] = GetHumanStatus::STATUS_PENDING;
                $model['paymentId'] = urldecode($response->PaymentId);
                $model['paymentUrl'] = urldecode($response->PaymentRedirectUrl);
                $payment->setDetails($model);
                throw new HttpRedirect($model['paymentUrl']);
            }
            $model['status'] = GetHumanStatus::STATUS_FAILED;
            if (isset($response)) {
                $model['errors'] = $response->Errors;
            }
        } elseif ($model['status'] === GetHumanStatus::STATUS_PENDING) {
            $response = $this->api->getPaymentState($model['paymentId']);
            if ($response->RequestSuccessful) {
                switch ($response->Status) {
                    case 'Succeeded':
                        $model['status'] = GetHumanStatus::STATUS_CAPTURED;
                        break;
                    case 'Canceled':
                        $model['status'] = GetHumanStatus::STATUS_CANCELED;
                }
            }
        }
        $payment->setDetails($model);

    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface
            ;
    }
}
