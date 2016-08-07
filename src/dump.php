<?php

function d($data)
{
    echo Phact\Main\VarDumper::dump($data);
    die();
}