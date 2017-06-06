<?php

class DbParameters {

    public $limit;
    public $skip;
    public $collectionName;
    public $fields = [];

    public function createFilter($query) {

        $filters = [];
        $this->collectionName = $query[$this->getIndex("from", $query) + 1];
        $fieldsStr = "";

        $indexofFrom = $this->getIndex("from", $query);
        for ($i = $this->getIndex("select", $query) + 1; $i < $indexofFrom; $i++) {
            $fieldsStr = $fieldsStr . $query[$i];
        }
        $this->fields = explode(",", $fieldsStr);

        $indexEndCondition = $this->getIndexEnd($query);

        if ($this->getIndex("where", $query) != null) {
            $conditionStr = "";
            for ($i = $this->getIndex("where", $query) + 1; $i < $indexEndCondition; $i++) {
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

            if (count($andCondition) > 1) {

                $filters = $this->getAndFilter($andCondition, $delimiters);
            } else if (count($orCondition) > 1) {

                $filters['$or'] = [];
                $filters['$or'] = array_merge($filters['$or'], $this->getOrFilter($orCondition, $delimiters));
                //processing other simple operation
            } else {
                $filter = [];
                $filters = $this->getSimpleFilter($filter, $delimiters, $conditionStr);
            }
        }
        return $filters;
    }

    public function createOption($query) {

        $options = [];
        //Limit
        if (array_search('limit', $query) != null) {
            $this->limit = $query[$this->getIndex("limit", $query) + 1];
            $options["limit"] = $this->limit;
        }
        //Skip
        if (array_search('skip', $query) != null) {
            $this->skip = $query[$this->getIndex("skip", $query) + 1];
            $options["skip"] = $this->skip;
        }
        //Order by
        if ($this->getIndex("order", $query) != null) {
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

    private function getIndex($param, $query) {
        return array_search($param, $query);
    }

    private function getIndexEnd($query) {
        if ($this->getIndex("order", $query) != null) {
            return $this->getIndex("order", $query);
        } else if ($this->getIndex("skip", $query) != null) {
            return $this->getIndex("skip", $query);
        } else if ($this->getIndex("limit", $query) != null) {
            return $this->getIndex("limit", $query);
        } else {
            return count($query);
        }
    }

    private function getAndFilter($andCondition, $delimiters) {
        $andfilter = [];
        foreach ($andCondition as $condt) {
            $andfilter = $this->getSimpleFilter($andfilter, $delimiters, $condt);
        }
        return $andfilter;
    }

    private function getSimpleFilter($filter, $delimiters, $conditionStr) {
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
        return $filter;
    }

    private function getOrFilter($orCondition, $delimiters) {
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
        return $orfilter;
    }

}
