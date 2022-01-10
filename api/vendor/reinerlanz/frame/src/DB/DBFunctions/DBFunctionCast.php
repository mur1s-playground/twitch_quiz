<?php


namespace Frame;


class DBFunctionCast {
    protected $args_expr = null;
    protected $argc = 2;

    public function getDescription() {
        return array(
            "Field"     => "CAST",
            "Type"      => "",
            "Null"      => "NO",
            "Key"       => "",
            "Default"   => null,
            "Extra"     => ""
        );
    }

    public function getSkeleton() {
        return array(
            ['str',     "CAST(" ],
            ['arg',     0       ],
            ['str',     " AS "  ],
            ['arg',     1       ],
            ['str',     ")"     ]
        );
    }
}