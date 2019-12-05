<?php

namespace AppBundle\Controller;

use FOS\UserBundle\Model\UserManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class LoopEatController extends AbstractController
{
    public function __construct(
        string $loopeatBaseUrl,
        string $loopeatClientId,
        string $loopeatClientSecret,
        LoggerInterface $logger)
    {
        $this->loopeatBaseUrl = $loopeatBaseUrl;
        $this->loopeatClientId = $loopeatClientId;
        $this->loopeatClientSecret = $loopeatClientSecret;
        $this->logger = $logger;
    }

    /**
     * @Route("/loopeat/oauth/callback", name="loopeat_oauth_callback")
     */
    public function connectStandardAccountAction(
        Request $request,
        JWTEncoderInterface $jwtEncoder,
        UserManagerInterface $userManager)
    {
        if (!$request->query->has('code')) {
            throw new BadRequestHttpException('Missing "code" parameter.');
        }

        $user = $this->getUser();
        if (null === $user) {
            throw new BadRequestHttpException('User is not authenticated.');
        }

        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $request->query->get('code'),
            'client_id' => $this->loopeatClientId,
            'client_secret' => $this->loopeatClientSecret,
        );

        $ch = curl_init(sprintf('%s/oauth/token', $this->loopeatBaseUrl));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $res = curl_exec($ch);

        $httpCode = !curl_errno($ch) ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : null;

        if ($httpCode !== 200) {

            $data = json_decode($res, true);

            $this->logger->error(sprintf('There was an "%s" error when trying to fetch an access token from LoopEat: "%s"',
                $data['error'], $data['error_description']));

            curl_close($ch);

            $this->addFlash('error', 'There was an error while trying to connect your LoopEat account.');

            return $this->redirectToRoute('fos_user_profile_show');
        }

        curl_close($ch);

        $data = json_decode($res, true);

        $user->setLoopeatAccessToken($data['access_token']);
        $user->setLoopeatRefreshToken($data['refresh_token']);

        $userManager->updateUser($user);

        $this->addFlash('notice', 'LoopEat account connected successfully!');

        return $this->redirectToRoute('fos_user_profile_show');
    }
}
