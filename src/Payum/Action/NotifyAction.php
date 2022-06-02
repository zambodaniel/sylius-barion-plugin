<?php

declare(strict_types=1);

namespace ZamboDaniel\SyliusBarionPlugin\Payum\Action;

use Hoa\Exception\Exception;
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
use Webmozart\Assert\Assert;

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
        Assert::isInstanceOf($payment, PaymentInterface::class);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (isset($model['paymentId']) && !empty($model['paymentId'])) {
            $response = $this->api->getPaymentState((string) $model['paymentId']);
            if ($response->RequestSuccessful) {
                // Call ok
                switch ($response->Status) {
                    case \PaymentStatus::Authorized:
                    case \PaymentStatus::Succeeded:
                        $model['status'] = GetHumanStatus::STATUS_AUTHORIZED;
                        break;
                    case \PaymentStatus::Canceled:
                        $model['status'] = GetHumanStatus::STATUS_CANCELED;
                        break;
                    case \PaymentStatus::Expired:
                        $model['status'] = GetHumanStatus::STATUS_EXPIRED;
                        break;
                }
                throw new HttpResponse('SUCCESS');
            } elseif (!empty($response->Errors)) {
                /** @var \ApiErrorModel $error */
                $error = $response->Errors[0];
                throw new HttpResponse((string) $error->Title, (int) $error->ErrorCode);
            }
            throw new HttpResponse('Request Unsuccessful', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        throw new HttpResponse('paymentId not found', Response::HTTP_NOT_FOUND);
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
