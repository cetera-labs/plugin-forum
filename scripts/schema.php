<?php
header('Content-Type: text/xml');

$schema = new Schema();
$xml = $schema->createXml(array(
    'forum_replace',
    'forum_replace_groups'
), array(
    'forums'
));

print $xml;

$f = fopen(__DIR__.'/'.PLUGIN_DB_SCHEMA, 'w');
fwrite($f, $xml);
fclose($f);