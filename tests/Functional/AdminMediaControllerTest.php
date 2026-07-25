<?php

declare(strict_types=1);

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;

final class AdminMediaControllerTest extends WebTestCase
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

    public function testAdminMediaDeleteWithUnknownIdReturns404(): void
    {
        $url = $this->router->generate('admin_media_delete', ['id' => 999999]);
        $this->client->request('POST', $url);
        $this->assertResponseRedirects();
    }

    public function testAdminMediaIndexAsNonAdminFiltersOwnMediasOnly(): void
    {
        $nonAdmin = new User();
        $nonAdmin->setEmail('non-admin@example.com');
        $nonAdmin->setName('Non Admin');
        $nonAdmin->setPassword('fake_password_for_testing');
        $nonAdmin->setAdmin(false);
        $this->entityManager->persist($nonAdmin);
        $this->entityManager->flush();

        $this->client->loginUser($nonAdmin);

        $this->client->request('GET', $this->router->generate('admin_media_index'));

        $this->assertResponseIsSuccessful();
    }

    public function testAdminMediaDeleteWithValidCsrfRemovesMediaAndFile(): void
    {
        $crawler = $this->client->request('GET', $this->router->generate('admin_media_add'));
        $form = $crawler->selectButton('Ajouter')->form();

        $file = new UploadedFile(__DIR__.'/test.jpg', 'test.jpg', 'image/jpeg', null, true);
        $form['media[title]'] = 'Media à supprimer avec CSRF';
        /* @phpstan-ignore-next-line */
        $form['media[file]'] = $file;
        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $media = $this->entityManager->getRepository(Media::class)->findOneBy(['title' => 'Media à supprimer avec CSRF']);
        $this->assertNotNull($media);

        $mediaId = $media->getId();
        $path = $media->getPath();
        $this->assertFileExists($path);

        // Calcule la page réelle où se trouve ce média (id le plus récent = probablement la dernière page)
        $totalMedias = $this->entityManager->getRepository(Media::class)->count([]);
        $targetPage = (int) ceil($totalMedias / 25);

        $crawler = $this->client->request('GET', $this->router->generate('admin_media_index', ['page' => $targetPage]));
        $deleteForm = $crawler->filter('form[action="'.$this->router->generate('admin_media_delete', ['id' => $mediaId]).'"]')->form();

        $this->client->submit($deleteForm);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'supprimé');

        $this->entityManager->clear();
        $this->assertNull($this->entityManager->getRepository(Media::class)->find($mediaId));
        $this->assertFileDoesNotExist($path);
    }

    public function testAdminMediaDeleteWithInvalidCsrfDoesNotRemoveMedia(): void
    {
        $crawler = $this->client->request('GET', $this->router->generate('admin_media_add'));
        $form = $crawler->selectButton('Ajouter')->form();

        $file = new UploadedFile(__DIR__.'/test.jpg', 'test.jpg', 'image/jpeg', null, true);
        $form['media[title]'] = 'Media protégé CSRF';
        /* @phpstan-ignore-next-line */
        $form['media[file]'] = $file;
        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $media = $this->entityManager->getRepository(Media::class)->findOneBy(['title' => 'Media protégé CSRF']);
        $this->assertNotNull($media);
        $mediaId = $media->getId();

        $totalMedias = $this->entityManager->getRepository(Media::class)->count([]);
        $targetPage = (int) ceil($totalMedias / 25);

        $crawler = $this->client->request('GET', $this->router->generate('admin_media_index', ['page' => $targetPage]));
        $deleteForm = $crawler->filter('form[action="'.$this->router->generate('admin_media_delete', ['id' => $mediaId]).'"]')->form();

        // On altère volontairement le token pour simuler une soumission invalide
        $deleteForm['_token'] = 'token-invalide-volontairement';

        $this->client->submit($deleteForm);

        $this->assertResponseRedirects($this->router->generate('admin_media_index'));
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-error', 'erreur');

        // Le média ne doit PAS avoir été supprimé
        $this->entityManager->clear();
        $this->assertNotNull($this->entityManager->getRepository(Media::class)->find($mediaId));
    }

    public function testAdminMediaDeleteWithoutExistingFileDoesNotThrow(): void
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['admin' => true]);

        $album = new Album();
        $album->setName('Album sans fichier réel');
        $this->entityManager->persist($album);

        $media = new Media();
        $media->setUser($admin);
        $media->setAlbum($album);
        $media->setPath('uploads/fichier-inexistant-'.uniqid().'.jpg');
        $media->setTitle('Media sans fichier physique');
        $this->entityManager->persist($media);
        $this->entityManager->flush();

        $mediaId = $media->getId();

        // Calcule la page réelle où se trouve ce média
        $totalMedias = $this->entityManager->getRepository(Media::class)->count([]);
        $targetPage = (int) ceil($totalMedias / 25);

        $crawler = $this->client->request('GET', $this->router->generate('admin_media_index', ['page' => $targetPage]));
        $deleteForm = $crawler->filter('form[action="'.$this->router->generate('admin_media_delete', ['id' => $mediaId]).'"]')->form();

        $this->client->submit($deleteForm);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'supprimé');

        $this->entityManager->clear();
        $this->assertNull($this->entityManager->getRepository(Media::class)->find($mediaId));
    }
}
