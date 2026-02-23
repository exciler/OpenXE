<?php

namespace OpenXE\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use erpooSystem;
use OpenXE\Entity\Users\User;
use OpenXE\Form\Login\ChangePasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    public function __construct(
        private readonly erpooSystem $app,
    ) {}


    #[Route('/lostPassword', name: 'app_login_lostpw')]
    public function lostPassword(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this
            ->createFormBuilder()
            ->add('username', TextType::class, ['label' => 'Benutzername'])
            ->add('submit', SubmitType::class, ['label' => 'Passwort zurücksetzen'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('lostpw', 'Eine E-Mail wurde an Ihre Adresse gesendet, sofern der Benutzername existiert.');
            $user = $em->getRepository(User::class)->findOneBy(['username' => $form->get('username')->getData()]);
            if ($user && ($user->getVergessenzeit() === null || $user->getVergessenzeit() < new DateTime('-10min'))) {
                $address = $user->getAdresse();
                $language = $user->getSprachebevorzugen();
                if (!$language) {
                    $language = $address->getSprache();
                }
                if (!$language) {
                    $language = 'deutsch';
                }

                $mailContent = $this->app->erp->GetGeschaeftsBriefText('passwortvergessen', $language, 0);
                $mailSubject = $this->app->erp->GetGeschaeftsBriefBetreff('passwortvergessen', $language, 0);
                if (empty($mailContent) && $language != 'deutsch') {
                    $language = 'deutsch';
                    $mailSubject = $this->app->erp->GetGeschaeftsBriefBetreff('passwortvergessen', $language, 0);
                    $mailContent = $this->app->erp->GetGeschaeftsBriefText('passwortvergessen', $language, 0);
                }
                if (empty($mailSubject)) {
                    $mailSubject = 'OpenXE Passwort zurücksetzen';
                }
                if (empty($mailContent)) {
                    $mailContent = "{ANREDE} {NAME} Bitte klicken Sie auf dem Link <a href=\"{URL}\">{URL}</a> um Ihr Xentral-Passwort zu ändern";
                }

                $user->setVergessencode($code = sha1(uniqid()));
                $user->setVergessenzeit(new DateTime());
                $em->flush();

                $mailContent = str_replace(['{NAME}', '{ANREDE}', '{URL}'],
                    [
                        $address->getName(),
                        $address->getAnschreiben(),
                        $this->generateUrl('app_login_pwreset', ['code' => $code]),
                    ],
                    $mailContent);

                $mailSuccessfullySent = $this->app->erp->MailSend(
                    $this->app->erp->GetFirmaMail(),
                    $this->app->erp->GetFirmaAbsender(),
                    $address->getEmail(),
                    $address->getName(),
                    $mailSubject,
                    $mailContent,
                    '',
                    0,
                    true,
                    '',
                    '',
                    true,
                );
            }
            return $this->redirectToRoute('app_login_lostpw');
        }

        return $this->render('login/lostPassword.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/resetPassword/{code}', name: 'app_login_pwreset', requirements: ['code' => '[a-f0-9]{3,}'])]
    public function resetPassword(
        string $code,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $form = $this->createForm(ChangePasswordType::class);

        $user = $em->getRepository(User::class)->findOneBy(['vergessencode' => $code]);
        if ($user === null || $user->getVergessenzeit() < new DateTime('-2hour')) {
            return $this->render('login/resetPassword.html.twig', ['linkInvalid' => true]);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));
            $user->setVergessenzeit(null);
            $user->setVergessencode(null);
            $em->flush();

            $this->addFlash('login', 'Passwort geändert');
            return $this->redirectToRoute('openxe_legacy_index');
        }

        return $this->render('login/resetPassword.html.twig', [
            'linkInvalid' => false,
            'form' => $form,
        ]);
    }
}
