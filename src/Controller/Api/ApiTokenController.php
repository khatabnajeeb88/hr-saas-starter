<?php

namespace App\Controller\Api;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/tokens')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ApiTokenController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'api_tokens_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $tokens = iterator_to_array($user->getApiTokens());

        return new JsonResponse(
            $this->serializer->serialize($tokens, 'json', ['groups' => 'api_token:read']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('', name: 'api_tokens_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $description = $data['description'] ?? 'API Token - ' . date('Y-m-d H:i');

        $token = new ApiToken();
        $token->setUser($user);
        $token->setDescription($description);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        // Start building response manually to include the full token ONCE
        $responseData = json_decode($this->serializer->serialize($token, 'json', ['groups' => 'api_token:read']), true);
        // Inject the full token explicitly
        $responseData['token'] = $token->getToken();

        return new JsonResponse($responseData, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_tokens_delete', methods: ['DELETE'])]
    public function delete(ApiToken $token): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($token->getUser() !== $user) {
            throw $this->createAccessDeniedException('You do not own this token.');
        }

        $this->entityManager->remove($token);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
