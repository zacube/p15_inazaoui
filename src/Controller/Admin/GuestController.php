<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function add(Request $request, UserRepository $userRepository): Response
    {
        return $this->render('admin/guest/add.html.twig');
    }

    #[Route('/admin/guest/block', name: 'admin_guest_block')]
    public function block(Request $request, UserRepository $userRepository): Response
    {
        return $this->render('admin/guest/block.html.twig');
    }

    #[Route('/admin/guest/delete', name: 'admin_guest_delete')]
    public function delete(Request $request, UserRepository $userRepository): Response
    {
        return $this->render('admin/guest/delete.html.twig');
    }
}