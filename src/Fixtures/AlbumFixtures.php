<?php

namespace App\Fixtures;

use App\Entity\Album;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AlbumFixtures extends Fixture
{
    public const ALBUM_REFERENCE_PREFIX = 'album-';
    public const NB_ALBUMS = 3;

    public function load(ObjectManager $manager): void
    {
        // Crée 3 albums
        for ($i = 0; $i < self::NB_ALBUMS; $i++) {
            $album = (new Album())
                ->setName('album' . ($i + 1));
            $manager->persist($album);
            $this->addReference(self::ALBUM_REFERENCE_PREFIX . $i, $album);
        }

        $manager->flush();
    }
}