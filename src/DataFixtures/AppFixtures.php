<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Enum\UserRole;
use App\Entity\Conseil;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\CityService;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly CityService $cityService,
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $standardUser = new User();
        $standardUser->setUsername('user');
        $standardUser->setPassword($this->hasher->hashPassword($standardUser, 'user'));
        $newCity1 = $this->cityService->getCityFromZipCode('75001'); // Example zip code
        $manager->persist($newCity1);
        $standardUser->setCity($newCity1);
        $manager->persist($standardUser);

        $adminUser = new User();
        $adminUser->setUsername('admin');
        $adminUser->setPassword($this->hasher->hashPassword($adminUser, 'admin'));
        $adminUser->setRole(UserRole::ADMIN->value);
        $newCity2 = $this->cityService->getCityFromZipCode('92130'); // Example zip code
        $manager->persist($newCity2);
        $adminUser->setCity($newCity2);
        $manager->persist($adminUser);

        // conseils
        for ($i = 324; $i <= 333; $i++) {
            $conseil = new Conseil();
            $conseil->setContent("Conseil numero $i");

            $months = range(1, 12);
            shuffle($months);

            $appliedMonths = array_slice($months, 0, random_int(1, 12));
            sort($appliedMonths);
            $conseil->setMonths($appliedMonths);
            $manager->persist($conseil);
        }

        $manager->flush();
    }
}
