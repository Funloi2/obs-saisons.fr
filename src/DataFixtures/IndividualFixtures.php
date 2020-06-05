<?php

namespace App\DataFixtures;

use App\Entity\Individual;
use App\Entity\Species;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class IndividualFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $speciesRepository = $manager->getRepository(Species::class);

        for ($i = 0; $i < 76; ++$i) {
            $individual = new Individual();
            $individual->setName($faker->sentence(6, true));
            $individual->setSpecies($speciesRepository->find($faker->numberBetween(1, 55)));
            $individual->setUser($this->getReference('user-'.$faker->randomDigit));
            $individual->setStation($this->getReference('station-'.$faker->randomDigit));
            $individual->setCreatedAt($faker->dateTimeThisDecade('now', 'Europe/Paris'));

            $manager->persist($individual);

            $this->addReference(sprintf('individual-%d', $i), $individual);
        }

        for ($c = 76; $c < 100; ++$c) {
            $individual = new Individual();
            $individual->setName($faker->sentence(6, true));
            $individual->setSpecies($speciesRepository->find($faker->numberBetween(56, 73)));
            $individual->setUser($this->getReference('user-'.$faker->randomDigit));
            $individual->setStation($this->getReference('station-'.$faker->randomDigit));
            $individual->setCreatedAt($faker->dateTimeThisDecade('now', 'Europe/Paris'));

            $manager->persist($individual);

            $this->addReference(sprintf('individual-%d', $c), $individual);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            StationFixtures::class,
            OdsStaticDataFixtures::class,
        ];
    }
}

