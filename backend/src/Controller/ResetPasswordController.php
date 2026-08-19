<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): JsonResponse {
        $data  = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        $user = $users->findOneBy(['email' => $email]);

        // Always return 200 to avoid email enumeration
        if (!$user) {
            return $this->json(['message' => 'If that email exists you will receive a reset link shortly.']);
        }

        $token     = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiresAt);
        $em->flush();

        $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
        $resetLink   = "{$frontendUrl}/reset-password?token={$token}";

        $mail = (new Email())
            ->from('noreply@crm.local')
            ->to($user->getEmail())
            ->subject('Reset your CRM password')
            ->html(
                "<p>Hi {$user->getFirstName()},</p>
                <p>Click the link below to reset your password. The link expires in 1 hour.</p>
                <p><a href=\"{$resetLink}\">{$resetLink}</a></p>
                <p>If you did not request a password reset, you can ignore this email.</p>"
            );

        $mailer->send($mail);

        return $this->json(['message' => 'If that email exists you will receive a reset link shortly.']);
    }

    #[Route('/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true);
        $token    = $data['token']    ?? '';
        $password = $data['password'] ?? '';

        if (strlen($password) < 8) {
            return $this->json(['error' => 'Password must be at least 8 characters.'], 422);
        }

        $user = $users->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['error' => 'Invalid or expired reset link.'], 422);
        }

        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $em->flush();

        return $this->json(['message' => 'Password updated successfully.']);
    }
}
