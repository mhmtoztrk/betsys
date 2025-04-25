<?php

$db = new DB();

$pid = 1;
$pid2 = 2;

$list = [
    1 => $pid,
    2 => $pid2,
];

$db->from('exp_table')
->where('pid',$pid)
->all();

$db->from('exp_table')
->leftJoin('options', '%s.oid = %s.oid')
->select('*')
->orderBy('created', 'DESC')
->where('pid',$pid)
->all();

$db->from('exp_table')
->where('pid',$pid)
->first();

$db->from('exp_table')
->where('pid',$list,'IN')
->all();

$db->update('exp_table')
->where('pid',$pid)
->set([
    'status' => 'active',
]);

$db->insert('exp_table')->set([
    'status' => 'active',
]);

$db->delete('exp_table')
->where('prid', $prid)
->where('service_group', $service_group)
->done();


$f = new File();
$new_file = $f->save($file);
$fid = $new_file['fid']; //dosyanın id'si