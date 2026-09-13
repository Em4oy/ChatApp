<?php
class View
{
    /**
     * Render a template from templates/ directory.
     * The $data array will be extracted to variables available inside the template.
     *
     * @param string $template Name of the template file without extension (e.g. 'index')
     * @param array $data
     * @return string
     * @throws Exception
     */
    public static function render(string $template, array $data = []): string
    {
        $file = __DIR__ . '/../../templates/' . $template . '.php';
        if (!file_exists($file)) {
            throw new Exception('Template not found: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    public static function display(string $template, array $data = []): void
    {
        echo self::render($template, $data);
    }
}

?>