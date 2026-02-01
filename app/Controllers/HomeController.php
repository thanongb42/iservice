<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Service;
use App\Models\NavMenu;
use App\Models\LearningResource;
use App\Models\TechNews;

/**
 * Home Controller
 * หน้าแรกของระบบ
 */
class HomeController extends Controller
{
    /**
     * Display home page
     */
    public function index()
    {
        $navMenuModel = new NavMenu();
        $serviceModel = new Service();
        $learningResourceModel = new LearningResource();
        $techNewsModel = new TechNews();

        $data = [
            'navMenus' => $navMenuModel->getMenuStructure(),
            'services' => $serviceModel->getActiveServices(),
            'learningResources' => $learningResourceModel->getFeatured(9),
            'techNews' => $techNewsModel->getPinned(4),
            'latestNews' => $techNewsModel->getPublished(8)
        ];

        return $this->view('home/index', $data);
    }

    /**
     * Test page
     */
    public function test()
    {
        return $this->view('test', [
            'title' => 'MVC Framework Test Page',
            'message' => 'MVC Architecture is working perfectly!'
        ]);
    }
}
