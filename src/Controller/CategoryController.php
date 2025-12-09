<?php

namespace App\Controller;


use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
    }

    #[Route(path: '/api/category', name: 'get_parent_category', methods: ['GET'])]
    public function getCategories(): Response
    {
         $categories = $this->categoryRepository->findBy(['parent' => null], ['title' => 'ASC']);
         $categoriesTab = [];
         foreach ($categories as $category) {
             $categoriesTab[] = ['label' => $category->getTitle(), 'id' => $category->getId()];
         }
         return new JsonResponse($categoriesTab);
    }

    #[Route(path: '/api/category/all', name: 'get_all_parent_category', methods: ['GET'])]
    public function getAllCategories(): Response
    {
        $categories = $this->categoryRepository->findBy(['parent' => null]);
        $categoriesTab = [];
        foreach ($categories as $category) {
            $childs = [];
            if($category->getEnfants()) {
                foreach ($category->getEnfants() as $child) {
                    $childs[] = ['label' => $child->getTitle(), 'id' => $child->getId()];
                }
            }
            $categoriesTab[] = ['label' => $category->getTitle(), 'id' => $category->getId(), 'children' => $childs];
        }
        return new JsonResponse($categoriesTab);
    }
}