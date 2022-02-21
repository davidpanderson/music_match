<?php

require_once("../inc/boinc_db.inc");

class Ensemble {
    static function insert($clause) {
        $db = BoincDb::get();
        $ret = $db->insert('ensemble', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function lookup_id($id) {
        $db = BoincDb::get();
        return $db->lookup_id($id, 'ensemble', 'Ensemble');
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'ensemble', $clause);
    }
    function delete() {
        $db = BoincDb::get();
        return $db->delete($this, 'ensemble');
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('ensemble', 'Ensemble', $where_clause);
    }
    static function lookup($clause) {
        $db = BoincDb::get();
        return $db->lookup('ensemble', 'Ensemble', $clause);
    }
}

class EnsembleMember {
    static function insert($clause) {
        $db = BoincDb::get();
        $ret = $db->insert('ensemble_member', $clause);
        if (!$ret) return 0;
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('ensemble_member', 'EnsembleMember', $where_clause);
    }
}

?>
