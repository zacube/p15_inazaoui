<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MediaRepositoryTest extends KernelTestCase
{
    private MediaRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(MediaRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testFindByAlbumReturnsAssociatedMedias(): void
    {
        $user = $this->createUser('media-repo-user1@example.com');
        $album = $this->createAlbum('Album avec medias');

        $media1 = $this->createMedia($user, $album, 'photo1.jpg');
        $media2 = $this->createMedia($user, $album, 'photo2.jpg');

        $result = $this->repository->findByAlbum($album);

        $this->assertCount(2, $result);
        $this->assertContainsEquals($media1, $result);
        $this->assertContainsEquals($media2, $result);
    }

    public function testFindByAlbumReturnsEmptyArrayWhenNoMediaAssociated(): void
    {
        $emptyAlbum = $this->createAlbum('Album vide');

        $result = $this->repository->findByAlbum($emptyAlbum);

        $this->assertSame([], $result);
    }

    public function testFindByUserReturnsAssociatedMedias(): void
    {
        $user = $this->createUser('media-repo-user3@example.com');
        $album = $this->createAlbum('Album pour user3');

        $media1 = $this->createMedia($user, $album, 'photo3.jpg');

        $result = $this->repository->findByUser($user);

        $this->assertCount(1, $result);
        $this->assertContainsEquals($media1, $result);
    }

    public function testFindByUserReturnsEmptyArrayWhenNoMediaAssociated(): void
    {
        $userWithoutMedia = $this->createUser('media-repo-user4@example.com');

        $result = $this->repository->findByUser($userWithoutMedia);

        $this->assertSame([], $result);
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName('Test User');
        $user->setPassword('fake_password_for_testing');
        $user->setAdmin(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createAlbum(string $name): Album
    {
        $album = new Album();
        $album->setName($name);

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return $album;
    }

    private function createMedia(User $user, Album $album, string $path): Media
    {
        $media = new Media();
        $media->setUser($user);
        $media->setAlbum($album);
        $media->setPath($path);
        $media->setTitle('Titre de test');

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }
}
