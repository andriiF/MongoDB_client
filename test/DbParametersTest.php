<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DbParametersTest
 *
 * @author andrii
 */
require_once '../DbParameters.php';

class DbParametersTest extends PHPUnit_Framework_TestCase {

    protected $object;

    public function setUp() {
        $this->object = new DbParameters();
    }

    public function tearDown() {

    }

    public function testCreateFilter() {
        $query = [
            0 => "select",
            1 => "*",
            2 => "from",
            3 => "test",
            4 => "where",
            5 => "number",
            6 => "=",
            7 => "3",
            8 => "or",
            9 => "number",
            10 => "=4"
        ];
        $result = [
            '$or' => [
                0 => [
                    "number" => 3
                ],
                1 => [
                    "number" => 4
                ]
            ]
        ];


        $this->assertEquals(
                $result
                , $this->object->createFilter($query));
    }

    public function testCreateOption() {
        $query = [
            0 => "select",
            1 => "*",
            2 => "from",
            3 => "test",
            4 => "order",
            5 => "by",
            6 => "number",
            7 => "desc",
            8 => "skip",
            9 => "1",
            10 => "limit",
            11 => "3"
        ];
        $result = [
            "limit" => "3",
            "skip" => "1",
            "sort" => [
                "number" => "-1"
            ],
            "projection" => [
            ]
        ];

        $this->assertEquals(
                $result
                , $this->object->createOption($query));
    }

}
