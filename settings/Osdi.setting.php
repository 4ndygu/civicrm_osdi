<?php
return array(
  'multisite_acl_enabled' => array(
    'group_name' => 'OSDI',
    'group' => 'osdi',
    'name' => 'server_time_zone',
    'type' => 'String', //Wish there was something to capture non-integer numbers
    'default' => 0,
    'add' => '5.4',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Time zone offset for OSDI interactions',
    'help_text' => 'Time zone offset of local server',
  ),
);
