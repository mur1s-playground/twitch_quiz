<?php


namespace Frame;


class DBFunctionAvg {
    protected $args_expr = null;
    protected $argc = 1;

    public function getDescription() {
        return array(
            "Field"     => "AVG",
            "Type"      => "",
            "Null"      => "NO",
            "Key"       => "",
            "Default"   => null,
            "Extra"     => ""
        );
    }

    public function getSkeleton() {
        return array(
            ['str',     "AVG("  ],
            ['arg',     0       ],
            ['str',     ")"     ]
        );
    }
}
