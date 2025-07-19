<?php
abstract class Calculator_Module {
    protected $slug;
    protected $name;

    public function __construct($slug, $name) {
        $this->slug = $slug;
        $this->name = $name;
        add_shortcode($this->get_shortcode(), array($this, 'render'));
    }

    public function get_slug() {
        return $this->slug;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_shortcode() {
        return 'cf_' . $this->slug;
    }

    abstract public function render($atts);

    abstract public function calculate($data);

    abstract public function render_admin_settings();
}