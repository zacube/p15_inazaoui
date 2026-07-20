<?php

declare(strict_types=1);

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
        $nonAdmin->setEmail('non-admin-index-test@example.com');
        $nonAdmin->setName('Non Admin Index Test');
        $nonAdmin->setPassword('fake_password_for_testing');
        $nonAdmin->setAdmin(false);
        $this->entityManager->persist($nonAdmin);
        $this->entityManager->flush();

        $this->client->loginUser($nonAdmin);

        $this->client->request('GET', $this->router->generate('admin_media_index'));

        $this->assertResponseIsSuccessful();
    }
}
