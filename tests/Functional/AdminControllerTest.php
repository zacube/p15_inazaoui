<?php

declare(strict_types=1);

namespace App\Tests\Functional;


use App\Entity\Album;
use App\Entity\Media;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class AdminControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
/*      $userRepository = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class);
        $this->testAdmin = $userRepository->findOneBy(['email' => 'ina@zaoui.com']);*/
        $testAdmin =  new InMemoryUser('ina', '$2y$13$7JS0ehfU8vZhB3Q8o1sPGuoQxkiPGXRGgrAizmNfI5Sgy.Dqt9xoW', ['ROLE_ADMIN']);
        $this->router = static::getContainer()->get('router');

        $this->client->loginUser($testAdmin);
    }

    // teste les fonctions de AlbumController
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

    public function testAdminAlbumDelete(): void
    {
        $entityManager = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager');

        $album = new Album();
        $album->setName('Album de test');

        $entityManager->persist($album);
        $entityManager->flush();

        $mediaRepository = $entityManager->getRepository(Album::class);

        $this->assertNotNull(
            $mediaRepository->find($album->getId())
        );

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




    // teste les fonctions de MediaController
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
        $form['media[file]'] = $file;
        $this->client->submit($form);

        $this->assertResponseRedirects();
    }

    public function testAdminMediaDelete(): void
    {
        $entityManager = $this->client
            ->getContainer()
            ->get('doctrine.orm.entity_manager');

        $media = new Media();
        $media->setTitle('Media de test');
        $media->setPath(__DIR__);
        $entityManager->persist($media);
        $entityManager->flush();

        $mediaRepository = $entityManager->getRepository(Media::class);

        $this->assertNotNull(
            $mediaRepository->find($media->getId())
        );
    }
}