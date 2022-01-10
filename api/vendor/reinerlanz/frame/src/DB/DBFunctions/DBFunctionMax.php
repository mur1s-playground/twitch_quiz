<?php


namespace Frame;


class DBFunctionMax {
    protected $args_expr = null;
    protected $argc = 1;

    public function getDescription() {
        return array(
            "Field"     => "MAX",
            "Type"      => "",
            "Null"      => "NO",
            "Key"       => "",
            "Default"   => null,
            "Extra"     => ""
        );
    }

    public function getSkeleton() {
        return array(
            ['str',     "MAX("  ],
            ['arg',     0       ],
            ['str',     ")"     ]
        );
    }
}
