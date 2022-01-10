<?php

namespace Frame;

require_once 'Limit.php';
require_once 'Order.php';

class DBTable {
    protected $DBO = null;

    protected $table_name = null;
    protected $fields = null;
    protected $function_fields = null;
    protected $joins = null;

    protected $fieldSet = null;
    protected $fieldLocalSet = null;

    protected $resultSet = null;
    protected $function_result_row = null;

    public function __construct($table_name, $fields, $values = null) {
        $this->DBO = $GLOBALS['Boot']->getDBO();
        $this->table_name = $table_name;
        $this->fields = json_decode($fields, true);
        foreach ($this->fields as $field_name_camel => $field) {
            $child_setter_function = "set{$field_name_camel}";
            if (!is_null($values) && array_key_exists($field_name_camel, $values)) {
                $this->$child_setter_function($values[$field_name_camel]);
            } else {
                $this->$child_setter_function($field['Default']);
            }
        }
    }

    public function insert($lock = true, $set_id = false) {
        $query = "INSERT INTO `{$this->table_name}` (";
        foreach ($this->fields as $field_name_camel => $field) {
            if ($field['Extra'] == "auto_increment") {
                if ($lock) $ai_field = $field_name_camel;
                if (!$set_id) continue;
            }
            $query .= "`{$field['Field']}`,";
        }
        $query = rtrim($query, ',');
        $query .= ") VALUES (";

        $error = array();

        foreach ($this->fields as $field_name_camel => $field) {
            if ($field['Extra'] == "auto_increment" && !$set_id) continue;

            $child_getter_function = "get{$field_name_camel}";
            $value = $this->$child_getter_function();

            if (is_null($value)) {
                if ($field['Null'] == 'NO') {
                    $error[] = "{$field['Field']} = NULL not allowed for insertion in {$this->table_name}";
                }
                $query .= 'NULL,';
                continue;
            }

            if ($this->isTextType($field['Type'])) {
                $sanitised_value = $this->DBO->real_escape_string($value);
                $query .= "'{$sanitised_value}',";
            } else {
                if (!is_numeric($value)) {
                    $error[] = "non numeric value {$value} for {$this->table_name}.{$field['Field']}";
                }
                $query .= "{$value},";
            }
        }
        $query = rtrim($query, ',');
        $query .= ");";

        if (sizeof($error) > 0) {
            die(json_encode(array('status' => false, 'error' => $error), JSON_PRETTY_PRINT));
        }

        if ($lock) {
            $this->DBO->query("LOCK TABLES `{$this->table_name}` WRITE, `{$this->table_name}` AS `frame_maintable` READ;");
        }
        if ($this->DBO->query($query)) {
            if ($lock) {
                $order = new Order(get_class($this), $ai_field, Order::ORDER_DESC);
                $limit = new Limit(1);
                $this->find(null, null, $order, $limit);
                $this->next();
            }
        }
        if ($lock) {
            $this->DBO->query("UNLOCK TABLES;");
        }
    }

    public function find($condition = null, $joins = null, $orders = null, $limit = null, $fields = null, $group_by = null, $execute = true) {
        $error = array();

        if (!is_null($joins)) {
            if (!is_array($joins)) {
                $joins = [$joins];
            }

            $this->joins = $joins;
        }

        $this->fieldSet = $fields;

        if (!is_null($fields)) {
            $used_fields = $fields->getFields();
            $field_local_set = array();
            $field_local_set_joins = array();
            $function_fields = array();
            foreach ($used_fields as $field_desc) {
                $field_class = $field_desc[0];
                $field_name_camel = $field_desc[1];
                if ($field_class == get_class($this)) {
                    $field_local_set[] = $field_name_camel;
                } else if ($field_class == DBFunction::class) {
                    $function_fields[] = $field_name_camel;
                } else {
                    $j = 0;
                    if (sizeof($field_desc) == 3) {
                        $j = $field_desc[2];
                    }
                    for (; $j < sizeof($this->joins); $j++) {
                        if ($field_class == get_class($this->joins[$j]->getModel())) {
                            $field_local_set_joins[$j][] = $field_name_camel;
                            break;
                        }
                    }
                }
            }
            $this->fieldLocalSet = $field_local_set;
            $this->function_fields = $function_fields;
            foreach ($field_local_set_joins as $j => $field_local_set_join) {
                $this->joins[$j]->getModel()->fieldLocalSet($field_local_set_join);
            }
        }

        $query_join = "";
        if (!is_null($joins)) {
            $join_table_counter = 0;
            foreach ($this->joins as $join) {
                $query_join .= " {$join->getJoinType()} `{$join->getModel()->table_name}` AS `frame_join_{$join_table_counter}` ON ";
                $join_expr = $join->getExpr();

                $placeholders = array();
                preg_match_all("/\[[A-Za-z0-9_]+\]/", $join_expr, $placeholders, PREG_OFFSET_CAPTURE);

                $join_expr_array = array();

                $expr_cur_pos = 0;
                foreach ($placeholders[0] as $placeholder_arr) {
                    $placeholder_name = $placeholder_arr[0];
                    $placeholder_pos = $placeholder_arr[1];
                    if ($expr_cur_pos < $placeholder_pos) {
                        $join_expr_array[] = substr($join_expr, $expr_cur_pos, $placeholder_pos - $expr_cur_pos);
                        $expr_cur_pos = $placeholder_pos;
                    }
                    $join_expr_array[] = substr($join_expr, $expr_cur_pos, strlen($placeholder_name));
                    $expr_cur_pos += strlen($placeholder_name);
                }

                if ($expr_cur_pos < strlen($join_expr)) {
                    $join_expr_array[] = substr($join_expr, $expr_cur_pos);
                }

                for ($i = 0; $i < sizeof($join_expr_array); $i++) {
                    if (array_key_exists($join_expr_array[$i], $join->getValueArray())) {
                        $replacement = $join->getValueArray()[$join_expr_array[$i]];

                        if ($replacement[0][0] == get_class($this)) {
                            $field = $this->fields()[$replacement[0][1]];
                            $join_expr_array[$i] = "`frame_maintable`.`{$field['Field']}`";
                        } else if ($replacement[0][0] == Condition::CONDITION_CONST) {
                            if (is_numeric($replacement[0][1])) {
                                $join_expr_array[$i] = $replacement[0][1];
                            } else {
                                $join_expr_array[$i] = "'" . $this->DBO->real_escape_string($replacement[0][1]) . "'";
                            }
                        } else if ($replacement[0][0] == DBFunction::class) {
                            //currently only joining on selected function fields
                            //TODO: sanitise custom field name
                            $join_expr_array[$i] = "`{$replacement[0][1]}`";
                        } else {
                            $j = 0;
                            if (sizeof($replacement[0]) == 3) {
                                $j = $replacement[0][2];
                            }
                            for (; $j < sizeof($this->joins); $j++) {
                                if ($replacement[0][0] == get_class($this->joins[$j]->getModel())) {
                                    $field = $this->joins[$j]->getModel()->fields()[$replacement[0][1]];
                                    $join_expr_array[$i] = "`frame_join_{$j}`.`{$field['Field']}`";
                                    break;
                                }
                            }
                        }

                        $join_expr_array[$i] .= " {$replacement[1]} ";

                        if ($replacement[2][0] == get_class($this)) {
                            $field = $this->fields()[$replacement[2][1]];
                            $join_expr_array[$i] .= "`frame_maintable`.`{$field['Field']}`";
                        } else if ($replacement[2][0] == Condition::CONDITION_CONST) {
                            if (is_numeric($replacement[2][1])) {
                                $join_expr_array[$i] .= $replacement[2][1];
                            } else {
                                $join_expr_array[$i] .= "'" . $this->DBO->real_escape_string($replacement[2][1]) . "'";
                            }
                        } else if ($replacement[2][0] == DBFunction::class) {
                            //currently only joining on selected function fields
                            //TODO: sanitise custom field name
                            $join_expr_array[$i] = "`{$replacement[2][1]}`";
                        } else {
                            $j = 0;
                            if (sizeof($replacement[2]) == 3) {
                                $j = $replacement[2][2];
                            }
                            for (; $j < sizeof($this->joins); $j++) {
                                if ($replacement[2][0] == get_class($this->joins[$j]->getModel())) {
                                    $field = $this->joins[$j]->getModel()->fields()[$replacement[2][1]];
                                    $join_expr_array[$i] .= "`frame_join_{$j}`.`{$field['Field']}`";
                                    break;
                                }
                            }
                        }
                    }
                }
                $imploded_join = implode('', $join_expr_array);
                $query_join .= "({$imploded_join})";

                $join_table_counter++;
            }
        }

        if (is_null($fields)) {
            $query = "SELECT * FROM `{$this->table_name}` AS `frame_maintable`";
        } else {
            $query = "SELECT ";
            $used_fields = $fields->getFields();
            $field_selector = array();
            if (!is_null($this->joins)) {
                for ($j = 0; $j < sizeof($this->joins); $j++) {
                    $field_local_set_joins[$j] = array();
                }
            }
            foreach ($used_fields as $field_desc) {
                $field_class = $field_desc[0];
                $field_name_camel = $field_desc[1];
                if ($field_class == get_class($this)) {
                    $field = $this->fields()[$field_name_camel];
                    $field_selector[] = "`frame_maintable`.{$field['Field']}";
                } else if ($field_class == DBFunction::class) {
                    $function_name = $field_desc[2];
                    $function_expr = null;
                    if (sizeof($field_desc) == 4) $function_expr = $field_desc[3];
                    //TODO: sanitise $field_name_camel = custom field name
                    $field_selector[] = "{$this->parseFunctionExpression($function_name, $function_expr)} AS `{$field_name_camel}`";
                } else {
                    $j = 0;
                    if (sizeof($field_desc) == 3) {
                        $j = $field_desc[2];
                    }
                    for (; $j < sizeof($this->joins); $j++) {
                        if ($field_class == get_class($this->joins[$j]->getModel())) {
                            $field = $this->joins[$j]->getModel()->fields()[$field_name_camel];
                            $field_selector[] = "`frame_join_{$j}`.`{$field['Field']}`";
                            break;
                        }
                    }
                }
            }
            $query .= implode(',', $field_selector);
            $query .= " FROM `{$this->table_name}` AS `frame_maintable`";
        }

        $query .= "{$query_join}";

        if (!is_null($condition)) {
            $where = " WHERE ";

            $condition_expr = $condition->getExpr();

            $placeholders = array();
            preg_match_all("/\[[A-Za-z0-9_]+\]/", $condition_expr, $placeholders, PREG_OFFSET_CAPTURE);

            $condition_expr_array = array();

            $expr_cur_pos = 0;
            foreach ($placeholders[0] as $placeholder_arr) {
                $placeholder_name = $placeholder_arr[0];
                $placeholder_pos = $placeholder_arr[1];
                if ($expr_cur_pos < $placeholder_pos) {
                    $condition_expr_array[] = substr($condition_expr, $expr_cur_pos, $placeholder_pos - $expr_cur_pos);
                    $expr_cur_pos = $placeholder_pos;
                }
                $condition_expr_array[] = substr($condition_expr, $expr_cur_pos, strlen($placeholder_name));
                $expr_cur_pos += strlen($placeholder_name);
            }

            if ($expr_cur_pos < strlen($condition_expr)) {
                $condition_expr_array[] = substr($condition_expr, $expr_cur_pos);
            }

            for ($i = 0; $i < sizeof($condition_expr_array); $i++) {
                if (array_key_exists($condition_expr_array[$i], $condition->getValueArray())) {
                    $replacement = $condition->getValueArray()[$condition_expr_array[$i]];

                    if ($replacement[0][0] == get_class($this)) {
                        $field = $this->fields()[$replacement[0][1]];
                        $condition_expr_array[$i] = "`frame_maintable`.`{$field['Field']}`";
                    } else if ($replacement[0][0] == Condition::CONDITION_CONST) {
                        if (is_numeric($replacement[0][1])) {
                            $condition_expr_array[$i] = $replacement[0][1];
                        } else {
                            $condition_expr_array[$i] = "'" . $this->DBO->real_escape_string($replacement[0][1]) . "'";
                        }
		    } else if ($replacement[0][0] == Condition::CONDITION_RESERVED) {
			$condition_expr_array[$i] = $replacement[0][1];
                    } else if ($replacement[0][0] == DBFunction::class) {
                        //currently only cond on selected function fields
                        //TODO: sanitise custom field name
                        $condition_expr_array[$i] = "`{$replacement[0][1]}`";
                    } else {
                        $j = 0;
                        if (sizeof($replacement[0]) == 3) {
                            $j = $replacement[0][2];
                        }

                        for (; $j < sizeof($this->joins); $j++) {

                            if ($replacement[0][0] == get_class($this->joins[$j]->getModel())) {
                                $field = $this->joins[$j]->getModel()->fields()[$replacement[0][1]];
                                $condition_expr_array[$i] = "`frame_join_{$j}`.`{$field['Field']}`";
                                break;
                            }
                        }
                    }

                    $condition_expr_array[$i] .= " {$replacement[1]} ";

                    if ($replacement[2][0] == get_class($this)) {
                        $field = $this->fields()[$replacement[2][1]];
                        $condition_expr_array[$i] .= "`frame_maintable`.`{$field['Field']}`";
                    } else if ($replacement[2][0] == Condition::CONDITION_CONST) {
                        if (is_numeric($replacement[2][1])) {
                            $condition_expr_array[$i] .= $replacement[2][1];
                        } else {
                            $condition_expr_array[$i] .= "'" . $this->DBO->real_escape_string($replacement[2][1]) . "'";
                        }
		    } else if ($replacement[2][0] == Condition::CONDITION_QUERY) {
                        $condition_expr_array[$i] .= "(" . substr($replacement[2][1], 0, strlen($replacement[2][1])-1) . ")";
		    } else if ($replacement[2][0] == Condition::CONDITION_RESERVED) {
                        $condition_expr_array[$i] .= $replacement[2][1];
                    } else if ($replacement[2][0] == Condition::CONDITION_CONST_ARRAY) {
                        $condition_expr_array[$i] .= '(';
                        $tmp_cond_array = array();
                        foreach ($replacement[2][1] as $repl) {
                            if (is_numeric($repl)) {
                                $tmp_cond_array[] = $repl;
                            } else {
                                $tmp_cond_array[] = "'" . $this->DBO->real_escape_string($repl) . "'";
                            }
                        }
                        $condition_expr_array[$i] .= implode(',', $tmp_cond_array);
                        $condition_expr_array[$i] .= ')';
                    } else if ($replacement[2][0] == DBFunction::class) {
                        //currently only cond on selected function fields
                        //TODO: sanitise custom field name
                        $condition_expr_array[$i] = "`{$replacement[2][1]}`";
                    } else {
                        $j = 0;
                        if (sizeof($replacement[2]) == 3) {
                            $j = $replacement[2][2];
                        }

                        for (; $j < sizeof($this->joins); $j++) {
                            if ($replacement[2][0] == get_class($this->joins[$j]->getModel())) {
                                $field = $this->joins[$j]->getModel()->fields()[$replacement[2][1]];
                                $condition_expr_array[$i] .= "`frame_join_{$j}`.`{$field['Field']}`";
                                break;
                            }
                        }
                    }
                }
            }
            $query .= $where . implode('', $condition_expr_array);
        }

        if (!is_null($group_by)) {
            if (!is_array($group_by)) {
                $group_by = [$group_by];
            }

            $group_by_query = " GROUP BY ";
            $group_by_arr = array();
            foreach ($group_by as $group_by_col) {
                $table = $group_by_col->getClass();
                $field_name_camel = $group_by_col->getField();

                if ($table == get_class($this)) {
                    $table_field = $this->fields[$field_name_camel];
                    $group_by_arr[] = "`frame_maintable`.`{$table_field['Field']}`";
                } else if ($table == DBFunction::class) {
                    //currently only ordering by selected function fields
                    //TODO: sanitise custom field name
                    $group_by_arr[] = "`{$field_name_camel}`";
                } else {
                    $j = 0;
                    if ($group_by_col->getJoinOffset() > 0) {
                        $j = $group_by_col->getJoinOffset();
                    }
                    for (; $j < sizeof($this->joins); $j++) {
                        if ($table == get_class($this->joins[$j]->getModel())) {
                            $table_field = $this->joins[$j]->getModel()->fields()[$field_name_camel];
                            $group_by_arr[] = "`frame_join_{$j}`.`{$table_field['Field']}`";
                            break;
                        }
                    }
                }
            }
            $query .= $group_by_query . implode(',', $group_by_arr);
        }

        if (!is_null($orders)) {
            if (!is_array($orders)) {
                $orders = [$orders];
            }

            $order_query = " ORDER BY ";
            $order_arr = array();
            foreach ($orders as $order) {
                $table = $order->getClass();
                $field_name_camel = $order->getField();
                $sorting = $order->getOrdering();

                if ($table == get_class($this)) {
                    $table_field = $this->fields[$field_name_camel];
                    $order_arr[] = "`frame_maintable`.`{$table_field['Field']}` {$sorting}";
                } else if ($table == DBFunction::class) {
                    //currently only ordering by selected function fields
                    //TODO: sanitise custom field name
                    $order_arr[] = "`{$field_name_camel}`";
                } else {
                    $j = 0;
                    if ($order->getJoinOffset() > 0) {
                        $j = $order->getJoinOffset();
                    }
                    for (; $j < sizeof($this->joins); $j++) {
                        if ($table == get_class($this->joins[$j]->getModel())) {
                            $table_field = $this->joins[$j]->getModel()->fields()[$field_name_camel];
                            $order_arr[] = "`frame_join_{$j}`.`{$table_field['Field']}` {$sorting}";
                            break;
                        }
                    }
                }
            }
            $query .= $order_query . implode(',', $order_arr);
        }

        if (!is_null($limit)) {
	    $limit_cl = " LIMIT ";
	    if (!is_null($limit->getOffset())) {
                if (!is_numeric($limit->getOffset())) {
                    $error[] = "non int offset";
                }
                $limit_cl .= "{$limit->getOffset()}, ";
            }
            if (!is_numeric($limit->getLimit())) {
                $error[] = "non int limit";
            }
            $limit_cl .= "{$limit->getLimit()}";
	    $query .= $limit_cl;
        }

        $query .= ";";

        if (sizeof($error) > 0) {
            die(json_encode(array('status' => false, 'error' => $error)));
        }

	if (!$execute) return $query;

        $this->resultSet = $this->DBO->query($query);
    }

    public function count() {
        if (!is_null($this->resultSet) && $this->resultSet) {
            return mysqli_num_rows($this->resultSet);
        }
        return 0;
    }

    public function next() {
        if (!is_null($this->resultSet) && $this->resultSet && ($row = mysqli_fetch_row($this->resultSet)) != null) {
            $field_counter = 0;
            if (is_null($this->fieldSet)) {
                foreach ($this->fields as $field_name_camel => $field) {
                    $child_setter_function = "set{$field_name_camel}";
                    $this->$child_setter_function($row[$field_counter]);
                    $field_counter++;
                }
                if (!is_null($this->joins)) {
                    foreach ($this->joins as $join) {
                        foreach ($join->getModel()->fields() as $field_name_camel => $field) {
                            $child_setter_function = "set{$field_name_camel}";
                            $join->getModel()->$child_setter_function($row[$field_counter]);
                            $field_counter++;
                        }
                    }
                }
            } else {
                $used_fields = $this->fieldSet->getFields();
                foreach ($used_fields as $field_desc) {
                    $field_class = $field_desc[0];
                    $field_name_camel = $field_desc[1];
                    if ($field_class == get_class($this)) {
                        $child_setter_function = "set{$field_name_camel}";
                        $this->$child_setter_function($row[$field_counter]);
                        $field_counter++;
                    } else if ($field_class == DBFunction::class) {
                        $custom_name = $field_name_camel;
                        $this->function_result_row[$custom_name] = $row[$field_counter];
                        $field_counter++;
                    } else {
                        $j = 0;
                        if (sizeof($field_desc) == 3) {
                            $j = $field_desc[2];
                        }
                        for (; $j < sizeof($this->joins); $j++) {
                            if ($field_class == get_class($this->joins[$j]->getModel())) {
                                $child_setter_function = "set{$field_name_camel}";
                                $this->joins[$j]->getModel()->$child_setter_function($row[$field_counter]);
                                $field_counter++;
                                break;
                            }
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function save($deep = false) {
        $pairs_arr = array();
        $where = "";
        $error = array();
        foreach ($this->fields as $field_name_camel => $field) {
            if (!is_null($this->fieldLocalSet)) {
                if (!in_array($field_name_camel, $this->fieldLocalSet)) {
                    continue;
                }
            }
            $child_getter_function = "get{$field_name_camel}";
            $value = $this->$child_getter_function();

            if (is_null($value)) {
                if ($field['Null'] == 'NO') {
                    $error[] = "null not allowed for {$this->table_name}.{$field['Field']}";
                }
                $value = 'NULL';
            } else {
                if ($this->isTextType($field['Type'])) {
                    $value = "'" . $this->DBO->real_escape_string($value) . "'";
                } else {
                    if (!is_numeric($value)) {
                        $error[] = "non numeric value {$value} for {$this->table_name}.{$field['Field']}";
                    }
                }
            }

            if ($field['Key'] == 'PRI') {
                $where = " WHERE `{$field['Field']}` = {$value}";
                continue;
            }
            $pairs_arr[] = "`{$field['Field']}` = {$value}";
        }

        if ($where == "") {
            $error[] = "no where clause to specify row to update for {$this->table_name}";
        }

        if (sizeof($error) > 0) {
            die(json_encode(array('status' => false, 'error' => $error)));
        }

        $imploded_pairs = implode(',', $pairs_arr);

        $query = "UPDATE `{$this->table_name}` SET {$imploded_pairs}{$where};";
        $this->DBO->query($query);

        if ($deep) {
            if (!is_null($this->joins)) {
                foreach ($this->joins as $join) {
                    $join->getModel()->save();
                }
            }
        }
    }

    public function delete($deep = false) {
        $error = array();
        $query = "DELETE FROM `{$this->table_name}`";
        $where = "";
        foreach ($this->fields as $field_name_camel => $field) {
            if ($field['Key'] == 'PRI') {
                if (!is_null($this->fieldLocalSet)) {
                    if (!in_array($field_name_camel, $this->fieldLocalSet)) {
                        continue;
                    }
                }
                $child_getter_function = "get{$field_name_camel}";
                $value = $this->$child_getter_function();

                if ($this->isTextType($field['Type'])) {
                    $value = "'" . $this->DBO->real_escape_string($value) . "'";
                } else {
                    if (!is_numeric($value)) {
                        $error[] = "non numeric value {$value} for {$this->table_name}.{$field['Field']}";
                    }
                }

                $where = " WHERE `{$field['Field']}` = {$value}";
                break;
            }
        }
        if ($where == "") {
            $error[] = "no where clause to specify row to delete for {$this->table_name}";
        }

        if (sizeof($error) > 0) {
            die(json_encode(array('status' => false, 'error' => $error)));
        }

        $query .= "{$where};";
        $this->DBO->query($query);

        if ($deep) {
            if (!is_null($this->joins)) {
                foreach ($this->joins as $join) {
                    $join->getModel()->delete();
                }
            }
        }
    }

    public function truncate() {
	$query = "TRUNCATE TABLE `{$this->table_name}`";
	$this->DBO->query($query);
    }

    private function isTextType($type) {
        if (strpos($type, "char") !== false || strpos($type, "text") !== false || strpos($type, "date") !== false || strpos($type, "blob") !== false) {
            return true;
        }
        return false;
    }

    public function fields() {
        return $this->fields;
    }

    public function fieldLocalSet($fieldLocalSet) {
        $this->fieldLocalSet = $fieldLocalSet;
    }

    public function fieldLocalSetGet() {
        return $this->fieldLocalSet;
    }

    public function joinedModelByClass($class) {
        foreach ($this->joins as $join) {
            if ($class == get_class($join->getModel())) {
                return $join->getModel();
            }
        }
        return null;
    }

    public function joinedModelById($id) {
        return $this->joins[$id]->getModel();
    }

    public function toArray($deep = false) {
        $arr = array();
        if ($deep) {
            foreach ($this->fields as $field_name_camel => $field) {
                $child_getter_function = "get{$field_name_camel}";
                if (is_null($this->fieldLocalSet) || in_array($field_name_camel, $this->fieldLocalSet)) {
                    if (!array_key_exists(get_class($this), $arr)) $arr[get_class($this)] = array();
                    $arr[get_class($this)][$field_name_camel] = $this->$child_getter_function();
                }
            }
            if (!is_null($this->joins)) {
                foreach ($this->joins as $join_offset => $join) {
                    $model = $this->joinedModelById($join_offset);
                    $arr_idx = $join_offset;
                    if (!array_key_exists('Frame\Join', $arr) || !array_key_exists(get_class($model), $arr['Frame\Join'])) {
                        $arr_idx = get_class($model);
                    }
                    if (is_null($this->fieldLocalSet) && is_null($model->fieldLocalSetGet())) {
                        if (!array_key_exists('Frame\Join', $arr)) $arr['Frame\Join'] = array();
                        $arr['Frame\Join'][$arr_idx] = $model->toArray();
                    } else {
                        foreach ($model->fieldLocalSetGet() as $field_name_camel) {
                            if (!array_key_exists('Frame\Join', $arr)) $arr['Frame\Join'] = array();
                            $child_getter_function = "get{$field_name_camel}";
                            $arr['Frame\Join'][$arr_idx][$field_name_camel] = $model->$child_getter_function();
                        }
                    }
                }
            }
            if (!is_null($this->function_fields) && sizeof($this->function_fields) > 0) {
                $arr['Frame\DBFunction'] = $this->function_result_row;
            }
        } else {
            foreach ($this->fields as $field_name_camel => $field) {
                $child_getter_function = "get{$field_name_camel}";
                $arr[$field_name_camel] = $this->$child_getter_function();
            }
        }
        return $arr;
    }

    public function DBFunctionResult($custom_name) {
        return $this->function_result_row[$custom_name];
    }

    private function parseFunctionExpression($function_name, $expr) {
        if (!is_array($expr)) {
            $expr = array($expr);
        }

        $expr_array = array();
        foreach ($expr as $expr_ct => $expr_obj) {
            $expression = $expr_obj->getExpr();
            $value_array = $expr_obj->getValueArray();

            $expr_array[$expr_ct] = array();

            $placeholders = array();
            preg_match_all("/\[[A-Za-z0-9_]+\]/", $expression, $placeholders, PREG_OFFSET_CAPTURE);

            $expr_cur_pos = 0;
            foreach ($placeholders[0] as $placeholder_arr) {
                $placeholder_name = $placeholder_arr[0];
                $placeholder_pos = $placeholder_arr[1];
                if ($expr_cur_pos < $placeholder_pos) {
                    $expr_array[$expr_ct][] = substr($expression, $expr_cur_pos, $placeholder_pos - $expr_cur_pos);
                    $expr_cur_pos = $placeholder_pos;
                }
                $expr_array[$expr_ct][] = substr($expression, $expr_cur_pos, strlen($placeholder_name));
                $expr_cur_pos += strlen($placeholder_name);
            }

            if ($expr_cur_pos < strlen($expression)) {
                $expr_array[$expr_ct][] = substr($expression, $expr_cur_pos);
            }

            for ($i = 0; $i < sizeof($expr_array[$expr_ct]); $i++) {
                if (array_key_exists($expr_array[$expr_ct][$i], $value_array)) {
                    $replacement = $value_array[$expr_array[$expr_ct][$i]];

                    if ($replacement[0] == get_class($this)) {
                        $field = $this->fields()[$replacement[1]];
                        $expr_array[$expr_ct][$i] = "`frame_maintable`.`{$field['Field']}`";
                    } else if ($replacement[0] == Condition::CONDITION_CONST) {
                        if (is_numeric($replacement[1])) {
                            $expr_array[$expr_ct][$i] = $replacement[1];
                        } else {
                            $expr_array[$expr_ct][$i] = "'" . $this->DBO->real_escape_string($replacement[1]) . "'";
                        }
                    } else if ($replacement[0] == DBFunction::class) {
                        $sub_expr = null;
                        if (sizeof($replacement) == 3) $sub_expr = $replacement[2];
                        $expr_array[$expr_ct][$i] = $this->parseFunctionExpression($replacement[1], $sub_expr);
                    } else {
                        $j = 0;
                        if (sizeof($replacement) == 3) {
                            $j = $replacement[2];
                        }
                        for (; $j < sizeof($this->joins); $j++) {
                            if ($replacement[0] == get_class($this->joins[$j]->getModel())) {
                                $field = $this->joins[$j]->getModel()->fields()[$replacement[1]];
                                $expr_array[$expr_ct][$i] = "`frame_join_{$j}`.`{$field['Field']}`";
                                break;
                            }
                        }
                    }
                }
            }
        }
        $result = '';
        $function_class_full_name = "\Frame\DBFunction{$function_name}";
        require_once dirname(__FILE__) . "/DBFunctions/DBFunction{$function_name}.php";
        $function_class = new $function_class_full_name();
        $function_skeleton = $function_class->getSkeleton();
        foreach ($function_skeleton as $fs_value) {
            $fs_type = $fs_value[0];
            $fs_val = $fs_value[1];
            if ($fs_type == 'str') {
                $result .= $fs_val;
            } else if ($fs_type == 'arg') {
                $result .= implode('', $expr_array[$fs_val]);
            } else if ($fs_type == "arglist") {
                $fs_val_2 = $fs_value[2];
                if ($fs_val_2 == "inf") {
                    $fs_val_2 = sizeof($expr_array);
                }
                $expr_r = array();
                for ($i = $fs_val; $i < $fs_val_2; $i++) {
                    $expr_r[] = implode('', $expr_array[$i]);
                }
                $result .= implode(',', $expr_r);
            }
        }
        return $result;
    }
}
