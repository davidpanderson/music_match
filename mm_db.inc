<?php
// This file is part of Music Match.
// Copyright (C) 2022 David P. Anderson
//
// Music Match is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// Music Match is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with Music Match.  If not, see <http://www.gnu.org/licenses/>.
// --------------------------------------------------------------------

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
    static function lookup($clause) {
        $db = BoincDb::get();
        return $db->lookup('ensemble_member', 'EnsembleMember', $clause);
    }
    static function update($clause) {
        $db = BoincDb::get();
        return $db->update_aux('ensemble_member', $clause);
    }
    static function delete_aux($clause) {
        $db = BoincDb::get();
        return $db->delete_aux('ensemble_member', $clause);
    }
}

class Search {
    static function insert($clause) {
        $db = BoincDb::get();
        $ret = $db->insert('search', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function lookup_id($id) {
        $db = BoincDb::get();
        return $db->lookup_id($id, 'search', 'Search');
    }
    static function lookup($clause) {
        $db = BoincDb::get();
        return $db->lookup('search', 'Search', $clause);
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('search', 'Search', $where_clause);
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'search', $clause);
    }
}

?>
