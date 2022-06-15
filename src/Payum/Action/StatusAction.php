<?php

declare(strict_types=1);

namespace ZamboDaniel\SyliusBarionPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class StatusAction implements ActionInterface
{
    /**
     * @param GetStatusInterface $request
     * @return void
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();

        $details = $payment->getDetails();

        $status = $details['status'] ?? null;

        switch($status) {
            case null:
            case GetHumanStatus::STATUS_NEW:
                $request->markNew();
                break;
            case GetHumanStatus::STATUS_PENDING:
                $request->markPending();
                break;
            case GetHumanStatus::STATUS_CAPTURED:
                $request->markCaptured();
                break;
            case GetHumanStatus::STATUS_FAILED:
                $request->markFailed();
                break;
            case GetHumanStatus::STATUS_CANCELED:
                $request->markCanceled();
                break;
            case GetHumanStatus::STATUS_EXPIRED:
                $request->markExpired();
                break;
            case GetHumanStatus::STATUS_AUTHORIZED:
                $request->markAuthorized();
                break;
            default:
                $request->markUnknown();

        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getFirstModel() instanceof SyliusPaymentInterface
            ;
    }
}