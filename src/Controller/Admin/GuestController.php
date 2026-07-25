<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\GuestType;
use App\Repository\UserRepository;
use App\Service\PasswordNotification\FileLogPasswordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class GuestController extends AbstractController
{
    #[Route('/admin/guest', name: 'admin_guest_index')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        // pagination à 25 par défaut, limitée à 100 au max
        $perPage = min($request->query->getInt('perPage', 25), 100);

        $criteria = [];

        $guests = $userRepository->findBy(
            $criteria,
            ['id' => 'ASC'],
            $perPage,
            $perPage * ($page - 1)
        );
        $total = $userRepository->count($criteria);

        return $this->render('admin/guest/index.html.twig', [
            'guests' => $guests,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/admin/guest/add', name: 'admin_guest_add')]
    public function add(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, FileLogPasswordNotifier $notifier): Response
    {
        $user = new User();

        $file = dirname(__DIR__, 3).'/var/dev_passwords.log';
        $form = $this->createForm(GuestType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du mot de passe temporaire
            $tempPassword = bin2hex(random_bytes(6));

            $user->setPassword($passwordHasher->hashPassword($user, $tempPassword));
            $user->setMustChangePassword(true);

            $entityManager->persist($user);
            $entityManager->flush();

            $notifier->notify($user, $tempPassword);

            $this->addFlash('success', "L'invité a bien été ajouté");

            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/guest/block/{id}', name: 'admin_guest_block', methods: [Request::METHOD_POST])]
    public function block(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $page = $request->request->getInt('page', 1);

        if (!$this->isCsrfTokenValid('block-user-'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Une erreur est survenue, veuillez recharger la page');

            return $this->redirectToRoute('admin_guest_index');
        }

        $guest = $userRepository->find($id);
        if (!$guest) {
            $this->addFlash('warning', 'Invité introuvable.');

            return $this->redirectToRoute('admin_guest_index');
        }

        if ($guest->isOwner()) {
            $this->addFlash('warning', 'Impossible de bloquer le propriétaire du compte.');

            return $this->redirectToRoute('admin_guest_index');
        }

        $guest->setBlocked(!$guest->isBlocked());

        $entityManager->persist($guest);
        $entityManager->flush();

        return $this->redirectToRoute('admin_guest_index', ['page' => $page]);
    }

    #[Route('/admin/guest/delete/{id}', name: 'admin_guest_delete', methods: [Request::METHOD_POST])]
    public function delete(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, FileLogPasswordNotifier $notifier): Response
    {
        if (!$this->isCsrfTokenValid('delete-user-'.$id, $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_guest_index');
        }

        $page = $request->request->getInt('page', 1);

        $guest = $userRepository->find($id);
        if (!$guest) {
            $this->addFlash('warning', 'Invité introuvable.');

            return $this->redirectToRoute('admin_guest_index');
        }

        $name = $guest->getName();
        $notifier->forget($guest);

        $entityManager->remove($guest);
        $entityManager->flush();

        $this->addFlash('success', "Invité $name supprimé");

        return $this->redirectToRoute('admin_guest_index', ['page' => $page]);
    }
}
