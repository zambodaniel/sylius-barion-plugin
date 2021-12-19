<?php

declare(strict_types=1);

namespace ZamboDaniel\SyliusBarionPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class BarionGatewayConfigurationType  extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pos_key', TextType::class, [
            'required' => true
        ]);
        $builder->add('payee', EmailType::class, [
            'required' => true
        ]);
        $builder->add('env', ChoiceType::class, [
            'required' => true,
            'choices' => [
                'prod' => 'prod',
                'test' => 'test',
            ]
        ]);
    }

}