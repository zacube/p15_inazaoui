<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;

final class AdminControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private RouterInterface $router;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $userRepository = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class);
        $testAdmin = $userRepository->findOneBy(['email' => 'ina@zaoui.com']);
        $this->router = static::getContainer()->get('router');

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
        $entityManager = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager');

        $album = new Album();
        $album->setName('Album de test');
        $entityManager->persist($album);
        $entityManager->flush();

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
        $entityManager->clear(); // efface les entités conservées en mémoire par Doctrine
        $album = $entityManager
            ->getRepository(Album::class)
            ->find($id);

        $this->assertSame('Nouveau nom', $album->getName());
    }

    public function testAdminAlbumDelete(): void
    {
        $entityManager = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager');

        $album = new Album();
        $album->setName('Album de test');
        $entityManager->persist($album);
        $entityManager->flush();

        $albumId = $album->getId();

        $url = $this->router->generate('admin_album_delete', ['id' => $albumId]);
        $this->client->request('GET', $url);

        $this->assertResponseRedirects();

        $entityManager->clear();
        $this->assertNull(
            $entityManager->getRepository(Album::class)->find($albumId)
        );
    }

    // teste les fonctions de MediaController.php
    public function testAdminMediaIndexIsAccessibleForAdmin(): void
    {
        $url = $this->router->generate('admin_media_index');
        $this->client->request('GET', $url);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAdminMediaAddFormIsAccessible(): void
    {
        $url = $this->router->generate('admin_media_add');
        $this->client->request('GET', $url);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAdminMediaAddSubmissionRedirects(): void
    {
        $crawler = $this->client->request('GET', $this->router->generate('admin_media_add'));
        $form = $crawler->selectButton('Ajouter')->form();

        $file = new UploadedFile(
            __DIR__.'/test.jpg',
            'test.jpg',
            'image/jpeg',
            null,
            true // mode test
        );

        // soumet le formulaire
        $form['media[title]'] = 'Titre de test';
        /* @phpstan-ignore-next-line */
        $form['media[file]'] = $file;
        $this->client->submit($form);

        $this->assertResponseRedirects();
    }

    public function testAdminMediaDelete(): void
    {
        // Crée un média de test via le formulaire
        $crawler = $this->client->request('GET', $this->router->generate('admin_media_add'));
        $form = $crawler->selectButton('Ajouter')->form();

        $file = new UploadedFile(
            __DIR__.'/test.jpg',
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        $form['media[title]'] = 'Titre de test';
        /* @phpstan-ignore-next-line */
        $form['media[file]'] = $file;
        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Vérifie que le média est en base
        $entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $media = $entityManager->getRepository(Media::class)->findOneBy(['title' => 'Titre de test']);
        $this->assertNotNull($media, 'Le média "Titre de test" n\'a pas été créé en base.');

        $mediaId = $media->getId();

        // Supprime via la route directement (pour éviter les erreurs dues à la pagination)
        $this->client->request(
            'GET',
            $this->router->generate('admin_media_delete', ['id' => $mediaId])
        );
        $this->assertResponseRedirects();

        // Vérifie que le média a bien été supprimé
        $entityManager->clear();
        $this->assertNull(
            $entityManager->getRepository(Media::class)->find($mediaId),
            'Le média n\'a pas été supprimé.'
        );
    }
}
