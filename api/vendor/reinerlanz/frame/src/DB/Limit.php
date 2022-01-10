<?php

namespace Frame;

class Limit {
    private $limit = null;
    private $offset = null;

    public function __construct($limit, $offset = null) {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function getLimit() {
        return $this->limit;
    }

    public function getOffset() {
        return $this->offset;
    }
}