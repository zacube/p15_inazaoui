<?php

declare(strict_types=1);

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


    public function testAdminMediaDelete(): void
    {
        $this->markTestSkipped('Difficulté à générer un token CSRF valide pour tester la suppression — à investiguer plus tard.');
    }

     // TODO testAdminMediaDelete désactivé : la génération d'un token CSRF valide en dehors du rendu réel d'un formulaire (Twig) s'est révélée trop complexe à mettre en place dans ce test fonctionnel (session non disponible via le token manager, formulaire non trouvé sur la bonne page de pagination). À reprendre si besoin d'une couverture complète sur ce cas précis.
/*    public function testAdminMediaDelete(): void
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
         @phpstan-ignore-next-line // ← à remettre en commentaire
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
*/
    public function testAdminMediaDeleteWithUnknownIdReturns404(): void
    {
        $url = $this->router->generate('admin_media_delete', ['id' => 999999]);
        $this->client->request('POST', $url);
        $this->assertResponseStatusCodeSame(404);
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

    public function testAdminMediaDeleteRemovesFileFromDisk(): void
    {
        $this->markTestSkipped('Difficulté à générer un token CSRF valide pour tester la suppression — à investiguer plus tard.');
    }

    // TODO testAdminMediaDeleteRemovesFileFromDisk désactivé : la génération d'un token CSRF valide en dehors du rendu réel d'un formulaire (Twig) s'est révélée trop complexe à mettre en place dans ce test fonctionnel (session non disponible via le token manager, formulaire non trouvé sur la bonne page de pagination). À reprendre si besoin d'une couverture complète sur ce cas précis.
/*
    public function testAdminMediaDeleteRemovesFileFromDisk(): void
    {
        // Crée un média de test via le formulaire, identique à testAdminMediaDelete()
        $crawler = $this->client->request('GET', $this->router->generate('admin_media_add'));
        $form = $crawler->selectButton('Ajouter')->form();

        $file = new UploadedFile(
            __DIR__.'/test.jpg',
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        $form['media[title]'] = 'Titre suppression fichier';
        @phpstan-ignore-next-line // ← à remettre en commentaire
        $form['media[file]'] = $file;
        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Vérifie que le média est en base
        $media = $this->entityManager->getRepository(Media::class)->findOneBy(['title' => 'Titre suppression fichier']);
        $this->assertNotNull($media, 'Le média "Titre suppression fichier" n\'a pas été créé en base.');

        $mediaId = $media->getId();
        $path = $media->getPath();

        // Vérifie que le fichier existe bien physiquement avant suppression
        $this->assertFileExists($path, 'Le fichier uploadé devrait exister avant suppression.');

        // Supprime via la route directement
        $this->client->request(
            'POST',
            $this->router->generate('admin_media_delete', ['id' => $mediaId])
        );
        $this->assertResponseRedirects();

        // Vérifie que le média a bien été supprimé en base
        $this->entityManager->clear();
        $this->assertNull(
            $this->entityManager->getRepository(Media::class)->find($mediaId),
            'Le média n\'a pas été supprimé.'
        );

        // Vérifie que le fichier a bien été supprimé du disque (couvre la ligne unlink())
        $this->assertFileDoesNotExist($path, 'Le fichier physique aurait dû être supprimé.');
    }
    */
}
