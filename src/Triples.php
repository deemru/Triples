<?php

namespace deemru;
use \PDO;

class Triples
{
    public $db;
    public $childs;
    private $parent;
    private $name;
    private $proc;
    private $uno;
    private $merge;
    private $q;
    private $hi;

    public function __construct( $db, $name = '', $writable = false, $types = [], $indexes = [] )
    {
        if( is_string( $db ) )
        {
            if( substr( $db, 0, 7) !== 'sqlite:' )
                $db = 'sqlite:' . $db;
            $this->db = new PDO( $db, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ] );
            $this->db->exec( '
                PRAGMA temp_store = MEMORY;
                PRAGMA synchronous = NORMAL;
                PRAGMA journal_mode = WAL;
            ' );
        }
        else
        {
            if( isset( $db->parent ) )
                $db = $db->parent;
            $this->db = $db->db;
            $this->parent = $db;
            $db->childs = ( isset( $db->childs ) ? $db->childs : 0 ) + 1;
        }

        $this->name = $name;

        if( $writable )
        {
            if( !isset( $this->parent ) )
            {
                $this->db->exec( '
                    PRAGMA journal_size_limit = 2097152;
                    PRAGMA wal_autocheckpoint = 512;
                    PRAGMA optimize;
                    PRAGMA wal_checkpoint;
                ' );
            }

            $n = count( $types );
            if( $n === 0 )
                return;

            $content = '';
            for( $i = 0; $i < $n; $i++ )
                $content .= ( $i ? ', ' : '' ) . 'r' . $i . ' ' . $types[$i];

            $this->db->exec( 'CREATE TABLE IF NOT EXISTS ' . $name . '( ' . $content . ' )' );

            $n = count( $indexes );
            for( $i = 0; $i < $n; $i++ )
                if( $indexes[$i] )
                    $this->db->exec( 'CREATE INDEX IF NOT EXISTS ' . $name . '_r' . $i . '_index ON ' . $name . '( r' . $i . ' )' );
        }
    }

    public function __destruct()
    {
        if( isset( $this->parent ) )
            --$this->parent->childs;
    }

    public function name()
    {
        return $this->name;
    }

    public function begin()
    {
        if( isset( $this->proc ) || isset( $this->parent->proc ) )
            return;
        if( isset( $this->parent ) )
            $this->parent->proc = true;
        else
            $this->proc = true;

        return $this->db->beginTransaction();
    }

    public function commit()
    {
        if( !isset( $this->proc ) && !isset( $this->parent->proc ) )
            return;
        if( isset( $this->parent ) )
            unset( $this->parent->proc );
        else
            unset( $this->proc );

        return $this->db->commit();
    }

    public function rollback()
    {
        if( !isset( $this->proc ) && !isset( $this->parent->proc ) )
            return;
        if( isset( $this->parent ) )
            unset( $this->parent->proc );
        else
            unset( $this->proc );

        return $this->db->rollBack();
    }

    private function q( $r )
    {
        if( !isset( $this->q[$r] ) )
            $this->q[$r] = $this->db->prepare( 'SELECT * FROM ' . $this->name . ' WHERE r' . $r . ' = ?' );
        return $this->q[$r];
    }

    public function get( $r, $v )
    {
        $q = $this->q( $r );
        if( false === $q->execute( [ $v ] ) )
            return false;
        return $q;
    }

    public function getUno( $r, $v )
    {
        if( !isset( $this->uno[$r] ) )
            $this->uno[$r] = $this->db->prepare( 'SELECT * FROM ' . $this->name . ' WHERE r' . $r . ' = ? LIMIT 1' );

        $q = $this->uno[$r];
        if( $q->execute( [ $v ] ) )
            $uno = $q->fetchAll();

        return isset( $uno[0] ) ? $uno[0] : false;
    }

    public function getHigh( $r )
    {
        if( !isset( $this->hi[$r] ) )
            $this->hi[$r] = $this->db->prepare( 'SELECT r' . $r . ' FROM ' . $this->name . ' ORDER BY r' . $r . ' DESC LIMIT 1' );

        $q = $this->hi[$r];
        if( $q->execute() )
            $hi = $q->fetchAll();

        return isset( $hi[0][0] ) ? (int)$hi[0][0] : false;
    }

    public function merge( $vvvs, $kv = false )
    {
        if( !isset( $this->merge ) )
        {
            $content = '';
            $values = '';
            $n = $kv ? 2 : count( $vvvs[0] );
            for( $i = 0; $i < $n; $i++ )
            {
                $content .= ( $i ? ', ' : '' ) . 'r' . $i;
                $values .= ( $i ? ', ' : '' ) . '?';
            }

            $this->merge = $this->db->prepare( 'INSERT OR REPLACE INTO ' . $this->name . '( '. $content . ' ) VALUES( ' . $values . ' )' );
        }

        if( $kv )
            foreach( $vvvs as $k => $v )
                $this->merge->execute( [ $k, $v ] );
        else
            foreach( $vvvs as $vvv )
                $this->merge->execute( $vvv );
    }

    public function query( $query, $values = null )
    {
        if( false === ( $q = $this->db->prepare( $query ) ) )
            return false;

        if( false === $q->execute( $values ) )
            return false;

        return $q;
    }
}
