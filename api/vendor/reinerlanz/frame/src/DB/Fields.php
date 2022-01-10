<?php


namespace Frame;


class Fields {
    private $fields = array();

    public function __construct($fields) {
        $this->fields = $fields;
    }

    public function getFields() {
        return $this->fields;
    }

    public function addField($classname, $field, $join_offset = null) {
        if (is_null($join_offset)) {
            $this->fields[] = [$classname, $field];
        } else {
            $this->fields[] = [$classname, $field, $join_offset];
        }
    }

    public function addFunctionField($custom_name, $function_name, $function_expr) {
            $this->fields[] = [DBFunction::class, $custom_name, $function_name, $function_expr];
    }
}