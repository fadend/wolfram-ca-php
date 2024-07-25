<?php

function get_with_default($name, $default)
{
    return $_GET[$name] ?? $default;
}

function int_param_with_default($name, $default)
{
    return intval(float_param_with_default($name, $default));
}

function int_param_with_default_range($name, $default, $min, $max)
{
    return intval(float_param_with_default_range($name, $default, $min, $max));
}

function float_param_with_default($name, $default)
{
    return floatval(get_with_default($name, $default));
}

function float_param_with_default_range($name, $default, $min, $max)
{
    $value = float_param_with_default($name, $default);
    return min($max, max($min, $value));
}
