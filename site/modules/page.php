<?php
class Page {
    private $template;

    public function __construct($template) {
        $this->template = $template;
    }

    public function Render($data) {
        $output = file_get_contents($this->template);
        foreach ($data as $key => $value) {
            $output = str_replace("{{ $key }}", $value, $output);
        }
        return $output;
    }
}