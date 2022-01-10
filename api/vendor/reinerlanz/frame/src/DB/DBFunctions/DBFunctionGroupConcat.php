<?php


namespace Frame;


class DBFunctionGroupConcat {
    protected $args_expr = null;
    protected $argc = 2;

    public function getDescription() {
        return array(
            "Field"     => "GROUP_CONCAT",
            "Type"      => "",
            "Null"      => "NO",
            "Key"       => "",
            "Default"   => null,
            "Extra"     => ""
        );
    }

    public function getSkeleton() {
        return array(
            ['str',     "GROUP_CONCAT(" 	],
            ['arg',     0       		],
	    ['str', 	" ORDER BY "		],
	    ['arg', 	1			],
			//TODO: un-TMP
	    ['str', 	" ASC SEPARATOR ';')"	]
        );
    }
}
