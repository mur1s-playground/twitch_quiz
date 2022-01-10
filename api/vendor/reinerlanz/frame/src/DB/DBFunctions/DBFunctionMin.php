<?php


namespace Frame;


class DBFunctionMin {
    protected $args_expr = null;
    protected $argc = 1;

    public function getDescription() {
        return array(
            "Field"     => "MIN",
            "Type"      => "",
            "Null"      => "NO",
            "Key"       => "",
            "Default"   => null,
            "Extra"     => ""
        );
    }

    public function getSkeleton() {
        return array(
            ['str',     "MIN("  ],
            ['arg',     0       ],
            ['str',     ")"     ]
        );
    }
}
