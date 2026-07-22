<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MediaController extends AbstractController
{
    #[Route('/admin/media', name: 'admin_media_index')]
    public function index(Request $request, MediaRepository $mediaRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        // pagination à 25 par défaut, limitée à 100 au max)
        $perPage = min($request->query->getInt('perPage', 25), 100);

        $criteria = [];

        if (!$this->isGranted('ROLE_ADMIN')) {
            $criteria['user'] = $this->getUser();
        }

        $medias = $mediaRepository->findBy(
            $criteria,
            ['id' => 'ASC'],
            $perPage,
            $perPage * ($page - 1)
        );
        $total = $mediaRepository->count($criteria);

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/admin', name: 'admin_index')]
    public function admin(): RedirectResponse
    {
        return $this->redirectToRoute('admin_media_index');
    }

    #[Route('/admin/media/add', name: 'admin_media_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $media = new Media();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $isAdmin]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $user = $this->getUser();
                if (!$user instanceof User) {
                    throw new AccessDeniedException('User must be authenticated and of type User.');  // @codeCoverageIgnore
                }
                $media->setUser($user);
            }
            if ($media->getUser() && !in_array('ROLE_ADMIN', $media->getUser()->getRoles(), true)) {
                $media->setAlbum(null);
            }
            $media->setPath('uploads/'.md5(uniqid()).'.'.$media->getFile()->guessExtension());
            $media->getFile()->move('uploads/', $media->getPath());
            $entityManager->persist($media);
            $entityManager->flush();

            $this->addFlash('success', 'Le média a bien été ajouté');

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', [
            'form' => $form->createView(),
            'is_admin' => $isAdmin,
        ]);
    }

    #[Route('/admin/media/delete/{id}', name: 'admin_media_delete', methods: [Request::METHOD_POST])]
    public function delete(int $id, Request $request, MediaRepository $mediaRepository, EntityManagerInterface $entityManager): Response
    {
        $page = $request->request->getInt('page', 1);

        if (!$this->isCsrfTokenValid('delete-media-'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Une erreur est survenue, veuillez recharger la page');

            return $this->redirectToRoute('admin_guest_index');
        }

        $media = $mediaRepository->find($id);
        if (!$media) {
            $this->addFlash('error', 'Une erreur est survenue, veuillez recharger la page');

            return $this->redirectToRoute('admin_guest_index');
        }
        $name = $media->getTitle();
        $path = $media->getPath();
        $entityManager->remove($media);
        $entityManager->flush();

        if (file_exists($path)) {
            unlink($path);
        }

        $this->addFlash('success', "Invité $name supprimé");

        return $this->redirectToRoute('admin_media_index', ['page' => $page]);
    }
}
