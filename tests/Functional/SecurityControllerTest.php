<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testLoginPageWithBadUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->filter('form')->form([
            '_username' => 'ina@zaoui.com',
            '_password' => 'mauvaisMotDePasse',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Invalid credentials.');
    }

    public function testLoginPageWithCorrectUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->filter('form')->form([
            '_username' => 'ina@zaoui.com',
            '_password' => 'password',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/admin/media');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testLogoutInvalidatesSessionAndRedirects(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('ina@zaoui.com');

        $client->loginUser($user);
        $client->request('GET', '/admin');
        $this->assertResponseRedirects('/admin/media'); // connecté, redirigé vers l'index

        $client->request('GET', '/logout');
        $this->assertResponseRedirects(); // logout redirige (peu importe où, ici)

        $client->request('GET', '/admin');
        $this->assertResponseRedirects('/login'); // déconnecté, renvoyé au login
    }
}
