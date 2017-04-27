<?php

class DbParameters {

    public $limit;
    public $skip;
    public $collectionName;
    public $fields = [];

    public function createFilter($query) {

        $filters = [];
        $indexofSelect = array_search('select', $query);
        $indexofFrom = array_search('from', $query);
        $indexofWhere = array_search('where', $query);
        $this->collectionName = $query[$indexofFrom + 1];
        $fieldsStr = "";

        for ($i = $indexofSelect + 1; $i < $indexofFrom; $i++) {
            $fieldsStr = $fieldsStr . $query[$i];
        }
        $this->fields = explode(",", $fieldsStr);

        if (array_search('order', $query) != null) {
            $indexEndCondition = array_search('order', $query);
        } else if (array_search('skip', $query) != null) {
            $indexEndCondition = array_search('skip', $query);
        } else if (array_search('limit', $query) != null) {
            $indexEndCondition = array_search('limit', $query);
        } else {
            $indexEndCondition = count($query);
        }
        if ($indexofWhere != null) {
            $conditionStr = "";
            for ($i = $indexofWhere + 1; $i < $indexEndCondition; $i++) {
                $conditionStr = $conditionStr . $query[$i];
            }
            $conditionStr = str_replace("<>", '$ne', $conditionStr);
            $conditionStr = str_replace(">=", '$gte', $conditionStr);
            $conditionStr = str_replace("<=", '$lte', $conditionStr);
            $conditionStr = str_replace("<", '$lt', $conditionStr);
            $conditionStr = str_replace(">", '$gt', $conditionStr);
            $delimiters = array('=', '$ne', '$gte', '$lte', '$lt', '$gt');

            $andCondition = explode("and", $conditionStr);
            $orCondition = explode("or", $conditionStr);

            //processing And operation
            if (count($andCondition) > 1) {
                $andfilter = [];
                foreach ($andCondition as $condt) {
                    foreach ($delimiters as $delimiter) {
                        $values = explode($delimiter, $condt);
                        if (count($values) > 1) {

                            if ($delimiter == "=") {
                                $andfilter[$values[0]] = $values[1];
                            } else {
                                $andfilter[$values[0]] = array($delimiter => $values[1]);
                            }
                            break;
                        }
                    }
                }


                $filters = $andfilter;
                //processing or operation
            } else if (count($orCondition) > 1) {
                $orfilter = [];
                foreach ($orCondition as $condt) {
                    foreach ($delimiters as $delimiter) {
                        $values = explode($delimiter, $condt);
                        if (count($values) > 1) {

                            if ($delimiter == "=") {
                                $temp = [];
                                $temp[$values[0]] = $values[1];
                                array_push($orfilter, $temp);
                            } else {
                                $temp = [];
                                $temp[$values[0]] = array($delimiter => $values[1]);
                                array_push($orfilter, $temp);
                            }
                            break;
                        }
                    }
                }
                $filters['$or'] = [];
                $filters['$or'] = array_merge($filters['$or'], $orfilter);
                //processing other simple operation
            } else {
                foreach ($delimiters as $delimiter) {
                    $values = explode($delimiter, $conditionStr);
                    if (count($values) > 1) {

                        if ($delimiter == "=") {
                            $filter[$values[0]] = $values[1];
                        } else {
                            $filter[$values[0]] = array($delimiter => $values[1]);
                        }
                        break;
                    }
                }
                $filters = $filter;
            }
        }
        return $filters;
    }

    public function createOption($query) {

        $options = [];
        //Limit
        if (array_search('limit', $query) != null) {
            $this->limit = $query[array_search('limit', $query) + 1];
            $options["limit"] = $this->limit;
        }
        //Skip
        if (array_search('skip', $query) != null) {
            $this->skip = $query[array_search('skip', $query) + 1];
            $options["skip"] = $this->skip;
        }
        //Order by
        if (array_search('order', $query) != null) {
            $indexofOrderBy = array_search('order', $query) + 1;
            $sortElement = $query[$indexofOrderBy + 1];


            if ($query[$indexofOrderBy + 2] == "desc") {
                $orderOn = -1;
            } else {
                $orderOn = 1;
            }
            $options["sort"] = array($sortElement => $orderOn);
        }
        //processing fields
        if (count($this->fields) == 1 && ($this->fields[0] == "*")) {
            $options["projection"] = [];
        } else {
            $fields = [
                '_id' => 0
            ];
            foreach ($this->fields as $field) {
                if (preg_match("/[^A-Za-z0-9_-].*$/", $field)) {
                    $field = str_replace(".*", "", $field);
                }
                $fields[$field] = 1;
            }
            $options["projection"] = $fields;
        }
        return $options;
    }

}
