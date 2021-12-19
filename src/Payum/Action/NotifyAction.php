<?php

declare(strict_types=1);

namespace ZamboDaniel\SyliusBarionPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\Response;

final class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{

    use GatewayAwareTrait, BarionApiTrait;

    /**
     * {@inheritdoc}
     *
     * @param Notify $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (isset($details['paymentId']) && $details['paymentId']) {
            $response = $this->api->getPaymentState($details['paymentId']);
            if ($response->RequestSuccessful) {
                if('Succeeded' == $response->Status and $details['status'] === GetHumanStatus::STATUS_PENDING) {
                    $details['status'] = GetHumanStatus::STATUS_CAPTURED;
                }
                throw new HttpResponse(null, Response::HTTP_OK);
            }
        }
        throw new HttpResponse(null, Response::HTTP_FORBIDDEN);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof ArrayObject
            ;
    }

}