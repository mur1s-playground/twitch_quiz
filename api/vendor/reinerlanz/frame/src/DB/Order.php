<?php

namespace Frame;

class Order {
    const ORDER_ASC = "ASC";
    const ORDER_DESC = "DESC";

    private $class = "";
    private $field = "";
    private $ordering = "";
    private $join_offset = 0;

    public function __construct($class, $field, $ordering, $join_offset = 0) {
        $this->class = $class;
        $this->field = $field;
        $this->ordering = $ordering;
        $this->join_offset = $join_offset;
    }

    public function getClass() {
        return $this->class;
    }

    public function getField() {
        return $this->field;
    }

    public function getOrdering() {
        return $this->ordering;
    }

    public function getJoinOffset() {
        return $this->join_offset;
    }
}
