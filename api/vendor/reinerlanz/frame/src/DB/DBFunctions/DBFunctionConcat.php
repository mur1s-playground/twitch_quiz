<?php


namespace Frame;


class DBFunctionConcat {
    protected $args_expr = null;
    protected $argc = "inf";

    public function getDescription() {
        return array(
            "Field"     => "CONCAT",
            "Type"      => "",
            "Null"      => "NO",
            "Key"       => "",
            "Default"   => null,
            "Extra"     => ""
        );
    }

    public function getSkeleton() {
        return array(
            ['str'      , "CONCAT(" ],
            ['arglist'  , 0         , "inf"],
            ['str'      , ")"       ]
        );
    }
}