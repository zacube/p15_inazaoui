<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class HomeControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private User $testGuest;
    private ?RouterInterface $router;

    public function setUp(): void
    {
        $this->client = HomeControllerTest::createClient();
        $userRepository = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class);
        $this->testGuest = $userRepository->findOneBy(['admin' => false]);
        $this->router = HomeControllerTest::getContainer()->get('router');
    }

    /**
     * @return array<string[]>
     */
    public function providePublicUrls(): array
    {
        return [
            ['home'],
            ['guests'],
            ['portfolio'],
            ['about'],
        ];
    }

    /**
     * @dataProvider providePublicUrls
     */
    public function testPublicRoutesAreAccessible(string $routeName): void
    {
        $url = $this->router->generate($routeName);
        $this->client->request(Request::METHOD_GET, $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGuestRouteIsAccessible(): void
    {
        $url = $this->router->generate('guest', ['id' => $this->testGuest->getId()]);
        $this->client->request(Request::METHOD_GET, $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRedirectsToLoginWhenNotAuthenticated(): void
    {
        $url = $this->router->generate('admin_album_index');
        $this->client->request(Request::METHOD_GET, $url);
        $this->assertResponseRedirects(
            $this->router->generate('admin_login', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }
}
