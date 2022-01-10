<?php


namespace Frame;


class DBFunctionExpression {
    protected $expr = null;
    protected $value_array = null;

    public function __construct($expr, $value_array) {
        $this->expr = $expr;
        $this->value_array = $value_array;
    }

    public function getExpr() {
        return $this->expr;
    }

    public function getValueArray() {
        return $this->value_array;
    }
}