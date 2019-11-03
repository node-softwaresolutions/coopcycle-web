<?php

namespace AppBundle\Controller\Utils;

use AppBundle\Service\OrderManager;
use AppBundle\Sylius\Order\OrderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sylius\Component\Payment\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @see https://stripe.com/docs/payments/payment-intents/migration#migrating-to-payment-intents-with-manual-confirmation
 * @see https://stripe.com/docs/payments/payment-intents/web
 * @see https://stripe.com/docs/payments/payment-intents/web-manual
 */
trait StripeTrait
{
    public function confirmPayment(Request $request,
        OrderInterface $order,
        OrderManager $orderManager,
        ObjectManager $objectManager,
        LoggerInterface $logger)
    {
        $content = $request->getContent();
        if (!empty($content)) {
            $data = json_decode($content, true);
        }

        if (!isset($data['payment_method_id'])) {

            return new JsonResponse(['error' =>
                ['message' => 'No payment_method_id key found in request']
            ], 400);
        }

        $stripePayment = $order->getLastPayment(/* PaymentInterface::STATE_CART */);

        $orderManager->createPaymentIntent($order, $data['payment_method_id']);

        $objectManager->flush();

        if (PaymentInterface::STATE_FAILED === $stripePayment->getState()) {

            return new JsonResponse(['error' =>
                ['message' => $stripePayment->getLastError()]
            ], 400);
        }

        $logger->info(
            sprintf('Order #%d | Created payment intent %s', $order->getId(), $stripePayment->getPaymentIntent())
        );

        $response = [];

        if ($stripePayment->requiresUseStripeSDK()) {

            $logger->info(
                sprintf('Order #%d | Payment Intent requires action "%s"', $order->getId(), $stripePayment->getPaymentIntentNextAction())
            );

            $response = [
                'requires_action' => true,
                'payment_intent_client_secret' => $stripePayment->getPaymentIntentClientSecret()
            ];

        // When the status is "succeeded", it means we captured automatically
        // When the status is "requires_capture", it means we separated authorization and capture
        } elseif ('succeeded' === $stripePayment->getPaymentIntentStatus() || $stripePayment->requiresCapture()) {

            $logger->info(
                sprintf('Order #%d | Payment Intent status is "%s"', $order->getId(), $stripePayment->getPaymentIntentStatus())
            );

            // The payment didn’t need any additional actions and completed!
            // Handle post-payment fulfillment
            $response = [
                'requires_action' => false,
                'payment_intent' => $stripePayment->getPaymentIntent()
            ];

        } else {

            return new JsonResponse(['error' =>
                ['message' => 'Invalid PaymentIntent status']
            ], 400);
        }

        return new JsonResponse($response);
    }
}
