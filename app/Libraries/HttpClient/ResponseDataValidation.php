<?php
namespace App\Libraries\HttpClient;

interface ResponseDataValidation
{
    public const EXPECT_NOT_EMPTY = 1;
    public const EXPECT_NOT_NULL = 2;
    public const EXPECT_ARRAY = 3;
    public const EXPECT_ARRAY_NOT_EMPTY = 4;
    public const EXPECT_STRING_NOT_EMPTY = 5;
    public const EXPECT_BOOLEAN = 6;
}