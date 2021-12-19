<?php

declare(strict_types=1);

namespace ZamboDaniel\SyliusBarionPlugin\Payum;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use ZamboDaniel\SyliusBarionPlugin\Payum\Action\CaptureAction;
use ZamboDaniel\SyliusBarionPlugin\Payum\Action\NotifyAction;
use ZamboDaniel\SyliusBarionPlugin\Payum\Action\StatusAction;

final class BarionPaymentGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'barion_payment',
            'payum.factory_title' => 'Barion Payment',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),

        ]);
        $config['payum.api'] = function (ArrayObject $config) {
            return new BarionApi($config['pos_key'], $config['payee'], $config['env']);
        };
    }
}