<?php
require 'Database/database.php';
require 'Database/queryBuilder.php';

return new queryBuilder(Conn::connect());