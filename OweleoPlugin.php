<?php
class OweleoPlugin
{
    protected $pattern = '/.*/';
    protected $actions;

    public function __construct($pattern=null, array $actions) {
        if (!is_null($pattern)) {
            $this->pattern = $pattern;
        }
        $this->actions = $actions;
    }
    public function action($action) {
        return isset($this->actions[$action])? $this->actions[$action]: function() {};
    }
    public function pattern() {
        return $this->pattern;
    }
}

