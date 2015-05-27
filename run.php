#!/usr/bin/env php
<?php
namespace TinyCache;
require 'vendor/autoload.php';

$app = new TinyCache();
print $app->run();
