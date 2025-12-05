<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/two-factor')]
#[IsGranted('ROLE_USER')]
class TwoFactorController extends AbstractController
{
    #[Route('/setup', name: 'app_2fa_setup')]
    public function setup(TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTotpAuthenticationEnabled()) {
            $this->addFlash('info', 'Two-factor authentication is already enabled.');
            return $this->redirectToRoute('app_profile_edit');
        }

        // Generate secret if not exists
        if (!$user->getTotpSecret()) {
            $secret = $totpAuthenticator->generateSecret();
            $user->setTotpSecret($secret);
        }

        // Generate QR code
        $qrCodeContent = $totpAuthenticator->getQRContent($user);
        
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrCodeContent)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        $qrCodeDataUri = $result->getDataUri();

        return $this->render('two_factor/setup.html.twig', [
            'qrCodeDataUri' => $qrCodeDataUri,
            'secret' => $user->getTotpSecret(),
        ]);
    }

    #[Route('/enable', name: 'app_2fa_enable', methods: ['POST'])]
    public function enable(
        Request $request,
        TotpAuthenticatorInterface $totpAuthenticator,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $code = $request->request->get('code');

        if (!$code) {
            $this->addFlash('error', 'Please enter the verification code.');
            return $this->redirectToRoute('app_2fa_setup');
        }

        // Verify the code
        if (!$totpAuthenticator->checkCode($user, $code)) {
            $this->addFlash('error', 'Invalid verification code. Please try again.');
            return $this->redirectToRoute('app_2fa_setup');
        }

        // Code is valid, 2FA is now enabled (secret is already set)
        $entityManager->flush();

        $this->addFlash('success', 'Two-factor authentication has been enabled successfully.');

        return $this->redirectToRoute('app_profile_edit');
    }

    #[Route('/disable', name: 'app_2fa_disable', methods: ['POST'])]
    public function disable(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isTotpAuthenticationEnabled()) {
            $this->addFlash('info', 'Two-factor authentication is not enabled.');
            return $this->redirectToRoute('app_profile_edit');
        }

        $user->setTotpSecret(null);
        $entityManager->flush();

        $this->addFlash('success', 'Two-factor authentication has been disabled.');

        return $this->redirectToRoute('app_profile_edit');
    }
}
