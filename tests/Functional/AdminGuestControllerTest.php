<?php

declare(strict_types=1);

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

final class AdminGuestControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private RouterInterface $router;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->userRepository = $container->get(UserRepository::class);
        $testAdmin = $this->userRepository->findOneBy(['email' => 'ina@zaoui.com']);

        $this->router = $container->get('router');
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->client->loginUser($testAdmin);
    }

    // teste les fonctions de GuestController.php
    public function testAdminGuestIndexIsAccessibleForAdmin(): void
    {
        $url = $this->router->generate('admin_guest_index');
        $this->client->request('GET', $url);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAdminGuestIndexIsForbiddenForNonAdmin(): void
    {
        $testGuest = $this->userRepository->findOneBy(['email' => 'invite+1@example.com']);
        $this->client->loginUser($testGuest);

        $url = $this->router->generate('admin_guest_index');
        $this->client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminGuestAddFormIsAccessible(): void
    {
        $url = $this->router->generate('admin_guest_add');
        $this->client->request('GET', $url);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAdminGuestAddSubmissionRedirects(): void
    {
        $crawler = $this->client->request('GET', $this->router->generate('admin_guest_add'));
        $form = $crawler->selectButton('Ajouter')->form();

        // soumet le formulaire
        $form['guest[name]'] = 'Nom de test';
        $form['guest[email]'] = 'email@de.test';
        $form['guest[description]'] = 'description de test';
        $this->client->submit($form);

        $this->assertResponseRedirects();
    }

    public function testAdminGuestAddCreatesGuestWithCorrectDefaults(): void
    {
        $crawler = $this->client->request('GET', $this->router->generate('admin_guest_add'));
        $form = $crawler->selectButton('Ajouter')->form();
        $form['guest[name]'] = 'Nom de test 2';
        $form['guest[email]'] = 'email2@de.test';
        $form['guest[description]'] = 'description de test';
        $this->client->submit($form);

        $guest = $this->entityManager->getRepository(User::class)
            ->findOneBy(['name' => 'Nom de test 2']);

        $this->assertNotNull($guest);
        $this->assertTrue($guest->isMustChangePassword());
        $this->assertNotEmpty($guest->getPassword());
        $this->assertContains('ROLE_USER', $guest->getRoles());
    }

    public function testAdminGuestDelete(): void
    {
        // Crée un invité de test via le formulaire
        $crawler = $this->client->request('GET', $this->router->generate('admin_guest_add'));
        $form = $crawler->selectButton('Ajouter')->form();

        // soumet le formulaire
        $form['guest[name]'] = 'Nom de test';
        $form['guest[email]'] = 'email@de.test';
        $form['guest[description]'] = 'description de test';
        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Vérifie que l'invité est en base
        $guest = $this->entityManager->getRepository(User::class)->findOneBy(['name' => 'Nom de test']);
        $this->assertNotNull($guest, 'L\'invité "Nom de test" n\'a pas été créé en base.');

        $guestId = $guest->getId();

        // Supprime via la route directement (pour éviter les erreurs dues à la pagination)
        $this->client->request(
            'GET',
            $this->router->generate('admin_guest_delete', ['id' => $guestId])
        );
        $this->assertResponseRedirects();

        // Vérifie que le média a bien été supprimé
        $this->entityManager->clear();
        $this->assertNull(
            $this->entityManager->getRepository(User::class)->find($guestId),
            'L\'invité n\'a pas été supprimé.'
        );
    }

    public function testAdminGuestDeleteWithUnknownIdReturns404(): void
    {
        $url = $this->router->generate('admin_guest_delete', ['id' => 999999]);
        $this->client->request('GET', $url);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAdminAlbumDeleteWithMediasIsBlocked(): void
    {
        $album = new Album();
        $album->setName('Album avec média');
        $this->entityManager->persist($album);

        $user = $this->userRepository->findOneBy(['email' => 'ina@zaoui.com']);

        $media = new Media();
        $media->setTitle('Média test');
        $media->setPath('test.jpg');
        $media->setAlbum($album);
        $media->setUser($user);
        $this->entityManager->persist($media);
        $this->entityManager->flush();

        $url = $this->router->generate('admin_album_delete', ['id' => $album->getId()]);
        $this->client->request('GET', $url);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testAdminAlbumDeleteWithoutMediasSucceeds(): void
    {
        $album = new Album();
        $album->setName('Album-test vide');
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
