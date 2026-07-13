<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
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
        $perPage = 25;

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
    public function add(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();

        $file = dirname(__DIR__, 3).'/var/dev_passwords.log';
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du mot de passe temporaire
            $tempPassword = bin2hex(random_bytes(6));

            $user->setPassword($passwordHasher->hashPassword($user, $tempPassword));
            $user->setMustChangePassword(true);
            $nom = $user->getName();
            file_put_contents($file, "$nom|$tempPassword\n", FILE_APPEND | LOCK_EX);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/guest/block', name: 'admin_guest_block')]
    public function block(Request $request, UserRepository $userRepository): Response
    {
        return $this->render('admin/guest/block.html.twig');
    }

    #[Route('/admin/guest/delete/{id}', name: 'admin_guest_delete')]
    public function delete(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $guest = $userRepository->find($id);
        $entityManager->remove($guest);
        $entityManager->flush();

        // 1. Lire le fichier ligne par ligne
        $file = dirname(__DIR__, 3).'/var/dev_passwords.log';
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $nom = $guest->getName();

        // 2. Filtrer : garder toutes les lignes sauf celle qui correspond à $nom
        $lines = array_filter($lines, function ($line) use ($nom) {
            $parts = explode('|', $line);

            return $parts[0] !== $nom;
        });

        // 3. Réécrire le fichier sans la ligne supprimée
        file_put_contents($file, implode("\n", $lines)."\n", LOCK_EX);

        return $this->redirectToRoute('admin_guest_index');
    }
}
