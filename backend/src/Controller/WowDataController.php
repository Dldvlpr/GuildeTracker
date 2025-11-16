<?php

namespace App\Controller;

use App\Service\WowClassMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


class WowDataController extends AbstractController
{
    public function __construct(
        private readonly WowClassMapper $classMapper,
    ) {}

    
    #[Route('/api/wow/classes', name: 'api_wow_classes', methods: ['GET'])]
    public function getClasses(): JsonResponse
    {
        $classes = [];

        foreach ($this->classMapper->getAllClassesWithMetadata() as $id => $data) {
            $classes[] = [
                'id' => $id,
                'name' => $data['name'],
                'color' => $data['color'],
                'roles' => $data['roles'],
                'armor' => $data['armor'],
                'resource' => $data['resource'],
            ];
        }

        return $this->json($classes);
    }

    
    #[Route('/api/wow/classes/{id}', name: 'api_wow_class_show', methods: ['GET'])]
    public function getClass(int $id): JsonResponse
    {
        $data = $this->classMapper->getClassData($id);

        if (!$data) {
            return $this->json(['error' => 'Class not found'], 404);
        }

        return $this->json([
            'id' => $id,
            'name' => $data['name'],
            'color' => $data['color'],
            'roles' => $data['roles'],
            'armor' => $data['armor'],
            'resource' => $data['resource'],
        ]);
    }

    
    #[Route('/api/wow/races', name: 'api_wow_races', methods: ['GET'])]
    public function getRaces(): JsonResponse
    {
        $races = [];

        foreach ($this->classMapper->getAllRaces() as $id => $name) {
            $races[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        return $this->json($races);
    }
}
