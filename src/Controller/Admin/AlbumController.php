<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Form\AlbumType;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlbumController extends AbstractController
{
    #[Route('/admin/album', name: 'admin_album_index')]
    public function index(AlbumRepository $albumRepository): Response
    {
        $albums = $albumRepository->findAll();

        return $this->render('admin/album/index.html.twig', ['albums' => $albums]);
    }

    #[Route('/admin/album/add', name: 'admin_album_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $album = new Album();
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($album);
            $entityManager->flush();

            $this->addFlash('success', 'Nouvel album créé');

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/album/update/{id}', name: 'admin_album_update')]
    public function update(Request $request, int $id, AlbumRepository $albumRepository, EntityManagerInterface $entityManager): Response
    {
        $album = $albumRepository->find($id);
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', "L'album a été mis à jour");

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/update.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/album/delete/{id}', name: 'admin_album_delete')]
    public function delete(int $id, AlbumRepository $albumRepository, MediaRepository $mediaRepository, EntityManagerInterface $entityManager): Response
    {
        $album = $albumRepository->find($id);

        if (!$album) {
            $this->addFlash('warning', 'Album introuvable.');

            return $this->redirectToRoute('admin_album_index');
        }

        $mediaCount = $mediaRepository->count(['album' => $album]);
        if ($mediaCount > 0) {
            $this->addFlash('warning', 'Impossible de supprimer un album contenant des médias.');

            return $this->redirectToRoute('admin_album_index');
        }

        $name = $album->getName();
        $entityManager->remove($album);
        $entityManager->flush();

        $this->addFlash('success', "Album $name supprimé");

        return $this->redirectToRoute('admin_album_index');
    }
}
