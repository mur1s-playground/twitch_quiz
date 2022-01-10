<?php


namespace Frame;


class DBFunctionSum {
    protected $args_expr = null;
    protected $argc = 1;

    public function getDescription() {
        return array(
            "Field"     => "SUM",
            "Type"      => "int(11)",
            "Null"      => "NO",
            "Key"       => "",
            "Default"   => null,
            "Extra"     => ""
        );
    }

    public function getSkeleton() {
        return array(
            ['str',     "SUM("  ],
            ['arg',     0       ],
            ['str',     ")"     ]
        );
    }
}
