<?php

namespace App\Core;

/**
 * Request Class
 * จัดการ HTTP Request data
 */
class Request
{
    /**
     * Get request method
     */
    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Check if request is GET
     */
    public function isGet()
    {
        return $this->method() === 'GET';
    }

    /**
     * Check if request is POST
     */
    public function isPost()
    {
        return $this->method() === 'POST';
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get input value
     */
    public function input($key, $default = null)
    {
        // Check POST first, then GET
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    /**
     * Get POST value
     */
    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }

        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET value
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    /**
     * Get all input data
     */
    public function all()
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Check if input exists
     */
    public function has($key)
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    /**
     * Get only specified keys
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $result = [];

        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->input($key);
            }
        }

        return $result;
    }

    /**
     * Get all except specified keys
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $all = $this->all();

        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    /**
     * Get uploaded file
     */
    public function file($key)
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Check if file was uploaded
     */
    public function hasFile($key)
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get request URI
     */
    public function uri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Get request path
     */
    public function path()
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    /**
     * Get request URL
     */
    public function url()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
}
