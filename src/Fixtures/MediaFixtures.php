<?php

namespace App\Fixtures;

use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;


final class MediaFixtures extends Fixture implements DependentFixtureInterface
{
    private const NB_MEDIAS_ADMIN = 9;
    private const NB_MEDIAS_INVITE_MIN = 3;
    private const NB_MEDIAS_INVITE_MAX = 6;

    public function load(ObjectManager $manager): void
    {
        $mediaNumber = 1;

        /** @var User $admin */
        $admin = $this->getReference(UserFixtures::ADMIN_REFERENCE);

        // Médias pour l'admin, répartis dans les 3 albums
        for ($i = 1; $i <= self::NB_MEDIAS_ADMIN; $i++) {
            $albumIndex = ($i - 1) % AlbumFixtures::NB_ALBUMS;
            $album = $this->getReference(AlbumFixtures::ALBUM_REFERENCE_PREFIX . $albumIndex);
            $num = sprintf('%04d', ($mediaNumber));
            $media = (new Media())
                ->setUser($admin)
                ->setAlbum($album)
                ->setTitle('Titre' . $mediaNumber)
                ->setPath('uploads/' . $num);

            $manager->persist($media);
            $mediaNumber++;
        }

        // Médias des invités, sans album
        for ($u = 1; $u <= UserFixtures::NB_INVITES; $u++) {
            /** @var User $guest */
            $guest = $this->getReference(UserFixtures::USER_REFERENCE_PREFIX . $u);

            $nbMedias = random_int(self::NB_MEDIAS_INVITE_MIN, self::NB_MEDIAS_INVITE_MAX);

            for ($i = 1; $i <= $nbMedias; $i++) {
                $num = sprintf('%04d', ($mediaNumber));
                $media = (new Media())
                    ->setUser($guest)
                    ->setTitle('Titre' . $mediaNumber)
                    ->setPath('uploads/' . $num);

                $manager->persist($media);
                $mediaNumber++;
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            AlbumFixtures::class,
        ];
    }
}
