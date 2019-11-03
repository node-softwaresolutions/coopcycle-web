<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Controller\OrderController;
use AppBundle\Entity\Restaurant;
use AppBundle\Entity\StripePayment;
use AppBundle\Service\OrderManager;
use AppBundle\Sylius\Order\OrderInterface;
use AppBundle\Utils\OrderTimeHelper;
use Doctrine\Common\Persistence\ObjectManager;
use Prophecy\Argument;
use Psr\Log\NullLogger;
use SimpleBus\Message\Bus\MessageBus;
use Stripe;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use PHPUnit\Framework\TestCase;

class OrderControllerTest extends TestCase
{
    private $controller;

    private $objectManager;

    public function setUp(): void
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $commandBus = $this->prophesize(MessageBus::class);
        $orderTimeHelper = $this->prophesize(OrderTimeHelper::class);

        $this->controller = new OrderController(
            $this->objectManager->reveal(),
            $commandBus->reveal(),
            $orderTimeHelper->reveal(),
            new NullLogger()
        );
    }

    private function createOrder($intentStatus, $nextActionType = null, $clientSecret = null)
    {
        $restaurant = $this->prophesize(Restaurant::class);

        $payload = [
            'id' => 'pi_12345678',
            'status' => $intentStatus,
            'next_action' => null,
            'client_secret' => ''
        ];

        if ($nextActionType) {
            $payload['next_action'] = [ 'type' => $nextActionType ];
        }
        if ($clientSecret) {
            $payload['client_secret'] = $clientSecret;
        }

        $paymentIntent = Stripe\PaymentIntent::constructFrom($payload);

        $stripePayment = new StripePayment();
        $stripePayment->setPaymentIntent($paymentIntent);

        $order = $this->prophesize(OrderInterface::class);
        $order
            ->getRestaurant()
            ->willReturn($restaurant->reveal());
        $order
            ->getLastPayment(/* PaymentInterface::STATE_CART */)
            ->willReturn($stripePayment);
        $order
            ->getId()
            ->willReturn(1);

        return $order->reveal();
    }

    public function testConfirmPaymentActionWithNextAction()
    {
        $cartContext = $this->prophesize(CartContextInterface::class);
        $orderManager = $this->prophesize(OrderManager::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $order = $this->createOrder('requires_source_action', 'use_stripe_sdk', '123456');

        $cartContext
            ->getCart()
            ->willReturn($order);

        $payload = [
            'payment_method_id' => 'pm_123456'
        ];

        $request = Request::create('/confirm-payment', 'POST',
            $parameters = [],
            $cookies = [],
            $files = [],
            $server = [],
            $content = json_encode($payload));

        $orderManager->createPaymentIntent($order, 'pm_123456')->shouldBeCalled();
        $this->objectManager->flush()->shouldBeCalled();

        $response = $this->controller->confirmPaymentAction(
            $request,
            $cartContext->reveal(),
            $orderManager->reveal(),
            $translator->reveal()
        );

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('requires_action', $data);
        $this->assertArrayHasKey('payment_intent_client_secret', $data);

        $this->assertEquals(true, $data['requires_action']);
        $this->assertEquals('123456', $data['payment_intent_client_secret']);
    }

    public function testConfirmPaymentActionWithoutNextAction()
    {
        $cartContext = $this->prophesize(CartContextInterface::class);
        $orderManager = $this->prophesize(OrderManager::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $order = $this->createOrder('requires_capture');

        $cartContext
            ->getCart()
            ->willReturn($order);

        $payload = [
            'payment_method_id' => 'pm_123456'
        ];

        $request = Request::create('/confirm-payment', 'POST',
            $parameters = [],
            $cookies = [],
            $files = [],
            $server = [],
            $content = json_encode($payload));

        $orderManager->createPaymentIntent($order, 'pm_123456')->shouldBeCalled();
        $this->objectManager->flush()->shouldBeCalled();

        $response = $this->controller->confirmPaymentAction(
            $request,
            $cartContext->reveal(),
            $orderManager->reveal(),
            $translator->reveal()
        );

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('requires_action', $data);
        $this->assertArrayHasKey('payment_intent', $data);

        $this->assertEquals(false, $data['requires_action']);
        $this->assertEquals('pi_12345678', $data['payment_intent']);
    }

    public function testConfirmPaymentActionWithInvalidPayload()
    {
        $cartContext = $this->prophesize(CartContextInterface::class);
        $orderManager = $this->prophesize(OrderManager::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $order = $this->createOrder('requires_capture');

        $cartContext
            ->getCart()
            ->willReturn($order);

        $request = Request::create('/confirm-payment', 'POST',
            $parameters = [],
            $cookies = [],
            $files = [],
            $server = [],
            $content = json_encode([]));

        $orderManager->createPaymentIntent(
            Argument::type(OrderInterface::class),
            Argument::type('string')
        )->shouldNotBeCalled();

        $this->objectManager->flush()
            ->shouldNotBeCalled();

        $response = $this->controller->confirmPaymentAction(
            $request,
            $cartContext->reveal(),
            $orderManager->reveal(),
            $translator->reveal()
        );

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data['error']);
    }
}
