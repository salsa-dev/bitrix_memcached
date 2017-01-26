<?php
return array (
  'cache' => array(
     'value' => array (
//        'type' => 'memcache',
        'memcache' => array(
//            'host' => 'unix:///tmp/memcached.sock',
            'host' => '/tmp/memcached.sock',
            'port' => '0'
        ),
        'type' => array(
            'class_name' => 'CPHPCacheMemcached',
            'required_file' => 'php_interface/cache_memcached.php'
        ),
        'sid' => $_SERVER["DOCUMENT_ROOT"]."#01"
     ),
  ),
);
?>
