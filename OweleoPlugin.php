<?php
/**
 * OweleoPlugin
 * @author Keisuke SATO <riaf@nequal.jp>
 **/
class OweleoPlugin
{
    protected $pattern;
    protected $actions;

    /**
     * コンストラクタ
     * @param string $pattern
     * @param array $actions
     **/
    public function __construct($pattern=null, array $actions) {
        if (!is_null($pattern)) {
            $this->pattern = $pattern;
        }
        $this->actions = $actions;
    }
    public function pattern() {
        return $this->pattern;
    }
    public function __call($method, $args) {
        return isset($this->actions[$method])? call_user_func_array($this->actions[$method], $args): null;
    }
}

