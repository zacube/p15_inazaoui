<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Album;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

final class AdminAlbumControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private RouterInterface $router;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $testAdmin = $userRepository->findOneBy(['email' => 'ina@zaoui.com']);

        $this->router = $container->get('router');
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->client->loginUser($testAdmin);
    }

    // teste les fonctions de AlbumController.php
    public function testAdminAlbumIndexIsAccessibleForAdmin(): void
    {
        $url = $this->router->generate('admin_album_index');
        $this->client->request('GET', $url);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAdminAlbumAddFormIsAccessible(): void
    {
        $url = $this->router->generate('admin_album_add');
        $this->client->request('GET', $url);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAdminAlbumAddSubmissionRedirects(): void
    {
        $crawler = $this->client->request('GET', $this->router->generate('admin_album_add'));
        $form = $crawler->selectButton('Ajouter')->form();
        // soumet le formulaire
        $form['album[name]'] = 'Album de test';
        $this->client->submit($form);

        $this->assertResponseRedirects();
    }

    public function testAdminAlbumUpdate(): void
    {
        $album = new Album();
        $album->setName('Album de test');
        $this->entityManager->persist($album);
        $this->entityManager->flush();

        $crawler = $this->client->request(
            'GET',
            $this->router->generate('admin_album_update', [
                'id' => $album->getId(),
            ])
        );

        $form = $crawler->selectButton('Modifier')->form();
        $form['album[name]'] = 'Nouveau nom';
        $this->client->submit($form);

        $this->assertResponseRedirects();

        $id = $album->getId();
        $this->entityManager->clear(); // efface les entités conservées en mémoire par Doctrine
        $album = $this->entityManager
            ->getRepository(Album::class)
            ->find($id);

        $this->assertSame('Nouveau nom', $album->getName());
    }

    public function testAdminAlbumDelete(): void
    {
        $album = new Album();
        $album->setName('Album de test');
        $this->entityManager->persist($album);
        $this->entityManager->flush();

        $albumId = $album->getId();

        $url = $this->router->generate('admin_album_delete', ['id' => $albumId]);
        $this->client->request('GET', $url);

        $this->assertResponseRedirects();

        $this->entityManager->clear();
        $this->assertNull(
            $this->entityManager->getRepository(Album::class)->find($albumId)
        );
    }
}
