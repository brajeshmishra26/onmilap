<?php

include('fns/HTMLPurifier/HTMLPurifier.auto.php');

class CustomClassDef extends HTMLPurifier_AttrDef {
    private $classes, $prefixes;

    public function __construct($classes, $prefixes) {
        $this->classes = $classes;
        $this->prefixes = is_array($prefixes) ? join('|', $prefixes) : $prefixes;
    }

    public function validate($string, $config, $context) {
        $classes = preg_split('/\s+/', $string);
        $validclasses = array();

        foreach ($classes as $class) {
            if (in_array($class, $this->classes) or
                preg_match("/^({$this->prefixes})/i", $class)) {

                $validclasses[] = $class;
            }
        }

        return join(' ', $validclasses);
    }
}

class HTMLPurifier_Strategy_Null extends HTMLPurifier_Strategy {
    public function execute($tokens, $config, $context) {
        return $tokens;
    }
}

class HTMLLinter extends HTMLPurifier {
    public function __construct($config = null) {
        parent::__construct($config);
        $this->strategy = new HTMLPurifier_Strategy_Null();
    }
}