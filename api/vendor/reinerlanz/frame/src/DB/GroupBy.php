<?php

namespace Frame;

class GroupBy {
    private $class = "";
    private $field = "";
    private $join_offset = 0;

    public function __construct($class, $field, $join_offset = 0) {
        $this->class = $class;
        $this->field = $field;
        $this->join_offset = $join_offset;
    }

    public function getClass() {
        return $this->class;
    }

    public function getField() {
        return $this->field;
    }

    public function getJoinOffset() {
        return $this->join_offset;
    }
}
