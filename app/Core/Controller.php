<?php

namespace App\Core;

/**
 * Base Controller Class
 * คลาสพื้นฐานสำหรับ Controllers ทั้งหมด
 */
class Controller
{
    protected $view;
    protected $request;
    protected $session;

    public function __construct()
    {
        $this->view = new View();
        $this->request = new Request();
        $this->session = new Session();
    }

    /**
     * Render view with layout
     */
    protected function view($view, $data = [], $layout = 'main')
    {
        return $this->view->render($view, $data, $layout);
    }

    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        return Response::json($data, $statusCode);
    }

    /**
     * Redirect to URL
     */
    protected function redirect($url)
    {
        return Response::redirect($url);
    }

    /**
     * Redirect back
     */
    protected function back()
    {
        return Response::back();
    }

    /**
     * Validate request data
     */
    protected function validate($rules)
    {
        return Validator::make($this->request->all(), $rules);
    }

    /**
     * Get request input
     */
    protected function input($key, $default = null)
    {
        return $this->request->input($key, $default);
    }

    /**
     * Check if request has input
     */
    protected function has($key)
    {
        return $this->request->has($key);
    }

    /**
     * Get all request data
     */
    protected function all()
    {
        return $this->request->all();
    }
}
