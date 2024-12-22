<?php

namespace App\DataFixtures;

use App\Entity\CategoryIngredient;
use App\Entity\CategoryPlat;
use App\Entity\IngredientCategory;
use App\Entity\Ingredient;
use App\Entity\Plat;
use App\Entity\Menu;
use App\Entity\Restaurant;
use App\Entity\Table;
use App\Entity\User;
use App\Entity\Reservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // 1. Catégories de plats
        $categoriesPlat = [];
        foreach (['Entrée', 'Plat principal', 'Dessert'] as $name) {
            $category = new CategoryPlat();
            $category->setName($name);
            $manager->persist($category);
            $categoriesPlat[] = $category;
        }

        // 2. Catégories d'ingrédients
        $categoriesIngredient = [];
        foreach (['Légume', 'Fruit', 'Viande', 'Poisson', 'Épice'] as $name) {
            $category = new CategoryIngredient();
            $category->setName($name);
            $manager->persist($category);
            $categoriesIngredient[] = $category;
        }

        // 3. Créer des utilisateurs
        $users = [];
        foreach ([
            ['email' => 'admin@example.com', 'password' => 'admin123', 'roles' => ['ROLE_ADMIN']],
            ['email' => 'user@example.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'banned@example.com', 'password' => 'banned123', 'roles' => ['ROLE_BANNED']],
        ] as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
            $manager->persist($user);
            $users[] = $user;
        }

        // 4. Restaurants et tables
        $restaurants = [];
        for ($i = 1; $i <= 5; $i++) {
            $restaurant = new Restaurant();
            $restaurant->setName($faker->company());
            $restaurant->setDescription($faker->sentence(10));
            $restaurant->setLocation($faker->address());

            for ($j = 1; $j <= 10; $j++) {
                $table = new Table();
                $table->setCapacity($faker->numberBetween(2, 8));
                $table->setRestaurantTables($restaurant);
                $manager->persist($table);
            }

            $manager->persist($restaurant);
            $restaurants[] = $restaurant;
        }

        // 5. Plats et ingrédients
        foreach ($restaurants as $restaurant) {
            $menu = new Menu();
            $menu->setRestaurant($restaurant);

            for ($k = 0; $k < 5; $k++) {
                $plat = new Plat();
                $plat->setName($faker->word());
                $plat->setPrice($faker->randomFloat(2, 10, 100));
                $plat->setDescription($faker->sentence());
                $plat->setCategories($categoriesPlat[array_rand($categoriesPlat)]);

                // Ajouter des ingrédients
                for ($l = 0; $l < 3; $l++) {
                    $ingredient = new Ingredient();
                    $ingredient->setName($faker->word());
                    $ingredient->setCategoryIngredient($categoriesIngredient[array_rand($categoriesIngredient)]);
                    $manager->persist($ingredient);

                    $plat->addIngredient($ingredient);
                }

                $manager->persist($plat);
                $menu->addPlat($plat);
            }

            $manager->persist($menu);
        }

        // 6. Réservations
        for ($r = 0; $r < 10; $r++) {
            $reservation = new Reservation();
            $reservation->setDate($faker->dateTimeBetween('now', '+1 month'));
            $reservation->setClient($users[array_rand($users)]);
            $manager->persist($reservation);
        }

        $manager->flush();
    }
}
