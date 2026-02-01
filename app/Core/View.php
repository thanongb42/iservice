<?php

namespace App\Core;

/**
 * View Class
 * จัดการ rendering views และ layouts
 */
class View
{
    protected $viewPath;
    protected $layoutPath;

    public function __construct()
    {
        $this->viewPath = BASE_PATH . '/app/Views/';
        $this->layoutPath = BASE_PATH . '/app/Views/layouts/';
    }

    /**
     * Render view with layout
     */
    public function render($view, $data = [], $layout = 'main')
    {
        // Extract data to variables
        extract($data);

        // Start output buffering
        ob_start();

        // Include view file
        $viewFile = $this->viewPath . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }

        require $viewFile;

        // Get view content
        $content = ob_get_clean();

        // If layout is specified, wrap content in layout
        if ($layout) {
            $layoutFile = $this->layoutPath . $layout . '.php';

            if (!file_exists($layoutFile)) {
                throw new \Exception("Layout file not found: {$layoutFile}");
            }

            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Render partial view (without layout)
     */
    public function partial($view, $data = [])
    {
        return $this->render($view, $data, null);
    }

    /**
     * Render component
     */
    public function component($component, $data = [])
    {
        extract($data);

        $componentFile = $this->viewPath . 'components/' . $component . '.php';

        if (!file_exists($componentFile)) {
            throw new \Exception("Component file not found: {$componentFile}");
        }

        require $componentFile;
    }

    /**
     * Include view file (for use inside views)
     */
    public static function include($view, $data = [])
    {
        $instance = new self();
        $instance->partial($view, $data);
    }
}
