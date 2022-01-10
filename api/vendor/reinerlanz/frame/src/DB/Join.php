<?php

namespace Frame;

class Join {
    const JOIN_INNER = 'INNER JOIN';
    const JOIN_LEFT = 'LEFT JOIN';

    private $model = "";
    private $join_type = self::JOIN_INNER;
    private $expr = "";
    private $value_array = "";

    public function __construct($model, $expr, $value_array, $join_type = self::JOIN_INNER) {
        $this->model = $model;
        $this->expr = $expr;
        $this->value_array = $value_array;
        $this->join_type = $join_type;
    }

    public function getModel() {
        return $this->model;
    }

    public function getJoinType() {
        return $this->join_type;
    }

    public function getExpr() {
        return $this->expr;
    }

    public function getValueArray() {
        return $this->value_array;
    }
}